<?php
namespace Framework\Auth;

use Framework\Request;
use Framework\Database\Query\QueryOperator;
use Framework\Auth\Schema\CredentialSchema;
use Framework\Auth\Schema\CredentialEntity;
use Framework\Auth\Schema\CredentialColumn;
use Framework\Auth\Schema\CredentialStatus;
use Framework\Auth\Schema\CredentialQuery;
use Framework\Intl\NLS;
use Framework\System\Access;
use Framework\System\Path;
use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Search;
use Framework\Utils\Select;
use Framework\Utils\Strings;
use Framework\Utils\Utils;

/**
 * The Auth Credential
 */
class Credential extends CredentialSchema {

    /**
     * Creates a Access Query
     * @param Access[]|Access|null $accessName
     * @param string[]|string|null $filter     Optional.
     * @param string|int           $value      Optional.
     * @return CredentialQuery
     */
    private static function createAccessQuery(
        array|Access|null $accessName,
        array|string|null $filter = null,
        string|int $value = 1,
    ): CredentialQuery {
        $accessNames = Access::toStrings($accessName);
        if (Arrays::isEmpty($accessNames)) {
            return new CredentialQuery();
        }

        $query = new CredentialQuery();
        $query->access->in($accessNames);

        if (!Arrays::isEmpty($filter)) {
            $filters = Arrays::toStrings($filter);
            foreach ($filters as $key) {
                $query->query->add($key, QueryOperator::Equal, $value);
            }
        }
        return $query;
    }



    /**
     * Returns the Credential with the given ID
     * @param int  $credentialID
     * @param bool $complete     Optional.
     * @return CredentialEntity
     */
    #[\Override]
    public static function getByID(int $credentialID, bool $complete = false): CredentialEntity {
        $query = new CredentialQuery();
        $query->credentialID->equal($credentialID);
        return self::getCredential($query, $complete);
    }

    /**
     * Returns the Credential with the given Email
     * @param string $email
     * @param bool   $complete Optional.
     * @return CredentialEntity
     */
    public static function getByEmail(string $email, bool $complete = true): CredentialEntity {
        $query = new CredentialQuery();
        $query->email->equal($email);
        return self::getCredential($query, $complete);
    }

    /**
     * Returns the Credential with the given Access Token
     * @param string $accessToken
     * @param bool   $complete    Optional.
     * @return CredentialEntity
     */
    public static function getByAccessToken(
        string $accessToken,
        bool $complete = true,
    ): CredentialEntity {
        $query = new CredentialQuery();
        $query->accessToken->equal($accessToken);
        $query->tokenExpiration->greaterThan(time());
        return self::getCredential($query, $complete);
    }



    /**
     * Returns true if there is an Credential with the given ID and Access(s)
     * @param int                  $credentialID
     * @param Access[]|Access      $accessName
     * @param string[]|string|null $filter       Optional.
     * @param string|int           $value        Optional.
     * @return bool
     */
    public static function existsWithAccess(
        int $credentialID,
        array|Access $accessName,
        array|string|null $filter = null,
        string|int $value = 1,
    ): bool {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return false;
        }

        $query = new CredentialQuery();
        $query->credentialID->equal($credentialID);
        return self::entityExists($query);
    }

    /**
     * Returns true if there is an Credential with the given Email
     * @param string               $email
     * @param int                  $skipID     Optional.
     * @param Access[]|Access|null $accessName Optional.
     * @return bool
     */
    public static function emailExists(
        string $email,
        int $skipID = 0,
        array|Access|null $accessName = null,
    ): bool {
        $query = self::createAccessQuery($accessName);
        $query->email->equal($email);
        $query->credentialID->notEqual($skipID);
        return self::entityExists($query);
    }

    /**
     * Returns true if there is an Credential with the given Value for the given Field
     * @param CredentialColumn $column
     * @param string|int       $value
     * @param int              $skipID Optional.
     * @return bool
     */
    public static function fieldExists(
        CredentialColumn $column,
        string|int $value,
        int $skipID = 0,
    ): bool {
        $query = new CredentialQuery();
        $query->add($column, QueryOperator::Equal, $value);
        $query->credentialID->notEqualIf($skipID);
        return self::entityExists($query);
    }



    /**
     * Returns all the Credentials
     * @param Request|null         $sort  Optional.
     * @param CredentialQuery|null $query Optional.
     * @return CredentialEntity[]
     */
    public static function getAll(?Request $sort = null, ?CredentialQuery $query = null): array {
        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns all the Credentials for the given Access(s)
     * @param Access[]|Access $accessName
     * @param Request|null    $sort       Optional.
     * @return CredentialEntity[]
     */
    public static function getAllForAccess(array|Access $accessName, ?Request $sort = null): array {
        $query = self::createAccessQuery($accessName);
        if ($query->isEmpty()) {
            return [];
        }

        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns all the Credentials for the given IDs
     * @param int[]        $credentialIDs
     * @param Request|null $sort          Optional.
     * @return CredentialEntity[]
     */
    public static function getAllWithIDs(array $credentialIDs, ?Request $sort = null): array {
        if (count($credentialIDs) === 0) {
            return [];
        }

        $query = new CredentialQuery();
        $query->credentialID->in($credentialIDs);
        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns all the Credentials for the given Access(s) and filter
     * @param Access[]|Access $accessName
     * @param string          $filter
     * @param string|int      $value      Optional.
     * @param Request|null    $sort       Optional.
     * @return CredentialEntity[]
     */
    public static function getAllWithFilter(
        array|Access $accessName,
        string $filter,
        string|int $value = 1,
        ?Request $sort = null,
    ): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns all the Credentials for the given Access(s)
     * @param Access[]|Access $accessName
     * @param Request|null    $sort       Optional.
     * @return CredentialEntity[]
     */
    public static function getActiveForAccess(
        array|Access $accessName,
        ?Request $sort = null,
    ): array {
        $query = self::createAccessQuery($accessName);
        if ($query->isEmpty()) {
            return [];
        }

        $query->statusEqual(CredentialStatus::Active);
        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns the latest Credentials for the given Access(s)
     * @param Access[]|Access $accessName
     * @param int             $amount
     * @param string|null     $filter     Optional.
     * @param string|int      $value      Optional.
     * @return CredentialEntity[]
     */
    public static function getLatestForAccess(
        array|Access $accessName,
        int $amount,
        ?string $filter = null,
        string|int $value = 1,
    ): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        $query->createdTime->orderByDesc();
        $query->limit($amount);
        return self::getCredentials($query, null, false);
    }

    /**
     * Returns the create times for all the Credentials with the given Access
     * @param int         $fromTime
     * @param Access|null $accessName Optional.
     * @return array<int,int>
     */
    public static function getAllCreateTimes(int $fromTime, ?Access $accessName = null): array {
        $query = self::createAccessQuery($accessName);
        $query->createdTime->greaterOrEqual($fromTime);

        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            $result[$elem->credentialID] = $elem->createdTime;
        }
        return $result;
    }

    /**
     * Returns the total amount of Credentials
     * @param CredentialQuery|null $query Optional.
     * @return int
     */
    public static function getTotalForQuery(?CredentialQuery $query = null): int {
        return self::getEntityTotal($query);
    }

    /**
     * Returns the total amount of Credentials for the given Access(s)
     * @param Access[]|Access $accessName
     * @param string|null     $filter     Optional.
     * @param string|int      $value      Optional.
     * @return int
     */
    public static function getTotalForAccess(
        array|Access $accessName,
        ?string $filter = null,
        string|int $value = 1,
    ): int {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return 0;
        }

        return self::getEntityTotal($query);
    }

    /**
     * Returns all the Credentials for the given Query
     * @param CredentialQuery|null $query    Optional.
     * @param Request|null         $sort     Optional.
     * @param bool                 $complete Optional.
     * @return CredentialEntity[]
     */
    private static function getCredentials(
        ?CredentialQuery $query = null,
        ?Request $sort = null,
        bool $complete = false,
    ): array {
        $list   = self::getEntityList($query, $sort);
        $result = [];

        foreach ($list as $elem) {
            $elem->accessName = Access::getName($elem->access);

            if ($elem->avatar !== "") {
                $elem->avatarFile = $elem->avatar;
                $elem->avatar     = Path::getAvatarsUrl($elem->avatar);
            }
            if (!$complete) {
                $elem->password        = "";
                $elem->salt            = "";
                $elem->accessToken     = "";
                $elem->tokenExpiration = 0;
            }
            $result[] = $elem;
        }
        return $result;
    }

    /**
     * Requests a single row from the database
     * @param CredentialQuery|null $query    Optional.
     * @param bool                 $complete Optional.
     * @return CredentialEntity
     */
    private static function getCredential(
        ?CredentialQuery $query = null,
        bool $complete = false,
    ): CredentialEntity {
        $list = self::getCredentials($query, null, $complete);
        if (isset($list[0])) {
            return $list[0];
        }
        return new CredentialEntity();
    }



    /**
     * Returns a select of all the Credentials
     * @return Select[]
     */
    public static function getSelect(): array {
        return self::requestSelect();
    }

    /**
     * Returns a select of Credentials for the given Access(s)
     * @param Access[]|Access      $accessName
     * @param string[]|string|null $filter     Optional.
     * @param string|int           $value      Optional.
     * @return Select[]
     */
    public static function getSelectForAccess(
        array|Access $accessName,
        array|string|null $filter = null,
        string|int $value = 1,
    ): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        $query->access->orderByDesc();
        return self::requestSelect($query);
    }

    /**
     * Returns a select of Credentials with the given IDs
     * @param int[] $credentialIDs
     * @return Select[]
     */
    public static function getSelectForIDs(array $credentialIDs): array {
        if (count($credentialIDs) === 0) {
            return [];
        }

        $query = new CredentialQuery();
        $query->credentialID->in($credentialIDs);
        $query->firstName->orderByAsc();
        return self::requestSelect($query);
    }

    /**
     * Returns the Credentials that contains the text and the given Accesses
     * @param string               $text
     * @param int                  $amount       Optional.
     * @param Access[]|Access|null $accessName   Optional.
     * @param int[]|int|null       $credentialID Optional.
     * @param bool                 $withEmail    Optional.
     * @param bool                 $splitValue   Optional.
     * @return Search[]
     */
    public static function search(
        string $text,
        int $amount = 10,
        array|Access|null $accessName = null,
        array|int|null $credentialID = null,
        bool $withEmail = true,
        bool $splitValue = true,
    ): array {
        $query = self::createAccessQuery($accessName);
        $query->search([
            CredentialColumn::FirstName,
            CredentialColumn::LastName,
            CredentialColumn::Email,
        ], $text, splitValue: $splitValue);

        if ($credentialID !== null) {
            $query->credentialID->in(Arrays::toInts($credentialID));
        }
        $query->limit($amount);

        $list   = self::getEntityList($query);
        $result = [];
        foreach ($list as $elem) {
            $result[] = new Search(
                $elem->credentialID,
                self::getName($elem, $withEmail),
            );
        }
        return $result;
    }

    /**
     * Returns a select of Credentials under the given conditions
     * @param CredentialQuery|null $query Optional.
     * @return Select[]
     */
    private static function requestSelect(?CredentialQuery $query = null): array {
        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            $result[] = new Select(
                $elem->credentialID,
                self::getName($elem),
            );
        }
        return $result;
    }

    /**
     * Returns a list of emails of the Credentials with the given Accesses
     * @param Access[]|Access      $accessName
     * @param string[]|string|null $filter     Optional.
     * @param string|int           $value      Optional.
     * @return array<string,string>
     */
    public static function getEmailsForAccess(
        array|Access $accessName,
        array|string|null $filter = null,
        string|int $value = 1,
    ): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        $list   = self::getEntityList($query);
        $result = [];
        foreach ($list as $elem) {
            $result[$elem->email] = $elem->language;
        }
        return $result;
    }



    /**
     * Returns true if the given password is correct for the given Credential ID
     * @param CredentialEntity|int $credential
     * @param string               $password
     * @return bool
     */
    public static function isPasswordCorrect(
        CredentialEntity|int $credential,
        string $password,
    ): bool {
        if (!($credential instanceof CredentialEntity)) {
            $credential = self::getByID($credential, true);
        }
        if ($credential->isEmpty()) {
            return false;
        }

        if ($credential->passExpiration > 0 && $credential->passExpiration < time()) {
            return false;
        }
        $hash = self::createHash($password, $credential->salt);
        return $hash["password"] === $credential->password;
    }

    /**
     * Returns true if the given Credential requires a password change
     * @param int    $credentialID Optional.
     * @param string $email        Optional.
     * @return bool
     */
    public static function reqPassChange(int $credentialID = 0, string $email = ""): bool {
        if ($credentialID === 0 && $email === "") {
            return false;
        }

        $query = new CredentialQuery();
        $query->startOr();
        $query->credentialID->equalIf($credentialID);
        $query->email->equalIf($email);
        $query->endOr();
        return self::getEntityValue($query, CredentialColumn::ReqPassChange) === 1;
    }



    /**
     * Creates a new Credential
     * @param Request|null             $request       Optional.
     * @param array<string,string|int> $fields        Optional.
     * @param Access|null              $accessName    Optional.
     * @param bool|null                $reqPassChange Optional.
     * @return int
     */
    public static function create(
        ?Request $request = null,
        array $fields = [],
        ?Access $accessName = null,
        ?bool $reqPassChange = null,
    ): int {
        $fields = self::parseFields($request, $fields, $accessName, $reqPassChange);
        $fields["lastLogin"]        = time();
        $fields["currentLogin"]     = time();
        $fields["askNotifications"] = 1;
        return self::createSchemaEntity($request, $fields);
    }

    /**
     * Edits the given Credential
     * @param int                      $credentialID
     * @param Request|null             $request       Optional.
     * @param array<string,string|int> $fields        Optional.
     * @param Access|null              $accessName    Optional.
     * @param bool|null                $reqPassChange Optional.
     * @param bool                     $skipEmpty     Optional.
     * @return bool
     */
    public static function edit(
        int $credentialID,
        ?Request $request = null,
        array $fields = [],
        ?Access $accessName = null,
        ?bool $reqPassChange = null,
        bool $skipEmpty = false,
    ): bool {
        $fields = self::parseFields($request, $fields, $accessName, $reqPassChange);
        return self::editSchemaEntity($credentialID, $request, $fields, skipEmpty: $skipEmpty);
    }

    /**
     * Updates the given Credential
     * @param int                 $credentialID
     * @param array<string,mixed> $fields
     * @return bool
     */
    public static function update(int $credentialID, array $fields): bool {
        return self::editSchemaEntity($credentialID, fields: $fields);
    }

    /**
     * Deletes the given Credential
     * @param int $credentialID
     * @return bool
     */
    public static function delete(int $credentialID): bool {
        return self::deleteEntity($credentialID);
    }

    /**
     * Destroys the Credential
     * @param int $credentialID
     * @return bool
     */
    public static function destroy(int $credentialID): bool {
        return self::editEntity(
            $credentialID,
            currentUser:      0,
            email:            "mail@mail.com",
            firstName:        NLS::getString("GENERAL_NAME"),
            lastName:         NLS::getString("GENERAL_LAST_NAME"),
            phone:            "",
            language:         "",
            avatar:           "",
            password:         "",
            salt:             "",
            reqPassChange:    false,
            passExpiration:   0,
            accessToken:      "",
            tokenExpiration:  0,
            observations:     "",
            sendEmails:       false,
            sendEmailNotis:   false,
            timezone:         0,
            currentLogin:     0,
            lastLogin:        0,
            askNotifications: false,
            status:           CredentialStatus::Inactive,
            isDeleted:        true,
        );
    }

    /**
     * Parses the data and returns the fields
     * @param Request|null             $request       Optional.
     * @param array<string,string|int> $fields        Optional.
     * @param Access|null              $accessName    Optional.
     * @param bool|null                $reqPassChange Optional.
     * @return array<string,string|int>
     */
    private static function parseFields(
        ?Request $request = null,
        array $fields = [],
        ?Access $accessName = null,
        ?bool $reqPassChange = null,
    ): array {
        $result = $fields;

        // Parse the Name
        $firstName = isset($result["firstName"]) ? Strings::toString($result["firstName"]) : "";
        $lastName  = isset($result["lastName"])  ? Strings::toString($result["lastName"])  : "";
        if ($request !== null) {
            $firstName = $request->getString("firstName");
            $lastName  = $request->getString("lastName");
        }
        $result["name"] = Strings::merge($firstName, $lastName);

        // Parse the Password
        $password = "";
        if ($request !== null) {
            $password = $request->getString("password");
        } elseif (isset($fields["password"])) {
            $password = Strings::toString($fields["password"]);
        }
        if ($password !== "") {
            $hash = self::createHash($password);
            $result["password"] = $hash["password"];
            $result["salt"]     = $hash["salt"];
        }

        // Parse the Access and Request Password Change
        if ($accessName !== null) {
            $result["access"] = $accessName->name;
        }
        if ($reqPassChange !== null) {
            $result["reqPassChange"] = $reqPassChange ? 1 : 0;
        }
        return $result;
    }



    /**
     * Sets the current User for the given Credential
     * @param int $credentialID
     * @param int $userID
     * @return bool
     */
    public static function setCurrentUser(int $credentialID, int $userID): bool {
        return self::editEntity($credentialID, currentUser: $userID);
    }

    /**
     * Sets the Credential Access
     * @param int    $credentialID
     * @param string $access
     * @return bool
     */
    public static function setAccess(int $credentialID, string $access): bool {
        return self::editEntity($credentialID, access: $access);
    }

    /**
     * Sets the Credential Email
     * @param int    $credentialID
     * @param string $email
     * @return bool
     */
    public static function setEmail(int $credentialID, string $email): bool {
        return self::editEntity($credentialID, email: $email);
    }

    /**
     * Updates the Language for the given Credential
     * @param int    $credentialID
     * @param string $language
     * @return bool
     */
    public static function setLanguage(int $credentialID, string $language): bool {
        $language = Strings::substringBefore($language, "-");
        return self::editEntity($credentialID, language: $language);
    }

    /**
     * Updates the Timezone for the given Credential
     * @param int $credentialID
     * @param int $timezone
     * @return bool
     */
    public static function setTimezone(int $credentialID, int $timezone): bool {
        return self::editEntity($credentialID, timezone: $timezone);
    }

    /**
     * Sets the Credential Avatar
     * @param int    $credentialID
     * @param string $avatar
     * @return bool
     */
    public static function setAvatar(int $credentialID, string $avatar): bool {
        return self::editEntity($credentialID, avatar: $avatar);
    }

    /**
     * Sets the Credential Appearance
     * @param int    $credentialID
     * @param string $appearance
     * @return bool
     */
    public static function setAppearance(int $credentialID, string $appearance): bool {
        return self::editEntity($credentialID, appearance: $appearance);
    }

    /**
     * Sets the Credential Password
     * @param int    $credentialID
     * @param string $password
     * @return array{password:string,salt:string}
     */
    public static function setPassword(int $credentialID, string $password): array {
        $hash = self::createHash($password);
        self::editEntity(
            $credentialID,
            password:       $hash["password"],
            salt:           $hash["salt"],
            passExpiration: 0,
            reqPassChange:  false,
        );
        return $hash;
    }

    /**
     * Sets the require password change for the given Credential
     * @param int  $credentialID
     * @param bool $reqPassChange
     * @return bool
     */
    public static function setReqPassChange(int $credentialID, bool $reqPassChange): bool {
        return self::editEntity($credentialID, reqPassChange: $reqPassChange);
    }

    /**
     * Sets a temporary Password to the given Credential
     * @param int $credentialID
     * @param int $hours        Optional.
     * @return string
     */
    public static function setTempPass(int $credentialID, int $hours = 48): string {
        $password = Strings::randomCode(10, "lud");
        $hash     = self::createHash($password);
        self::editEntity(
            $credentialID,
            password:       $hash["password"],
            salt:           $hash["salt"],
            passExpiration: time() + $hours * 3600,
            reqPassChange:  true,
        );
        return $password;
    }

    /**
     * Sets an Access Token to the given Credential
     * @param int $credentialID
     * @param int $hours        Optional.
     * @return string
     */
    public static function setAccessToken(int $credentialID, int $hours = 4): string {
        $expiration = $hours > 0 ? time() + $hours * 3600 : 0;
        $credential = self::getByID($credentialID, true);

        if ($credential->tokenExpiration > 0 && $credential->tokenExpiration < time()) {
            $accessToken = Strings::randomCode(30, "lud");
            self::editEntity($credentialID, tokenExpiration: $expiration);
            return $credential->accessToken;
        }

        $accessToken = Strings::randomCode(30, "lud");
        self::editEntity(
            $credentialID,
            accessToken:     $accessToken,
            tokenExpiration: $expiration,
        );
        return $accessToken;
    }

    /**
     * Removes an Access Token of the given Credential
     * @param int $credentialID
     * @return bool
     */
    public static function removeAccessToken(int $credentialID): bool {
        return self::editEntity(
            $credentialID,
            accessToken:     "",
            tokenExpiration: 0,
        );
    }

    /**
     * Stops asking the Credential for Notifications
     * @param int $credentialID
     * @return bool
     */
    public static function dontAskNotifications(int $credentialID): bool {
        return self::editEntity($credentialID, askNotifications: false);
    }

    /**
     * Updates the login time for the given Credential
     * @param int $credentialID
     * @return bool
     */
    public static function updateLoginTime(int $credentialID): bool {
        $currentLogin = self::getValue($credentialID, CredentialColumn::CurrentLogin);
        return self::editEntity(
            $credentialID,
            lastLogin:    Numbers::toInt($currentLogin),
            currentLogin: time(),
        );
    }

    /**
     * Sets the Credential Progress
     * @param int $credentialID
     * @param int $value
     * @return bool
     */
    public static function setProgress(int $credentialID, int $value): bool {
        return self::editEntity($credentialID, progressValue: $value);
    }

    /**
     * Sets the Credential Status
     * @param int              $credentialID
     * @param CredentialStatus $status
     * @return bool
     */
    public static function setStatus(int $credentialID, CredentialStatus $status): bool {
        return self::editEntity($credentialID, status: $status);
    }

    /**
     * Gets a Credential Value
     * @param int              $credentialID
     * @param CredentialColumn $column
     * @return mixed
     */
    public static function getValue(int $credentialID, CredentialColumn $column): mixed {
        $query = new CredentialQuery();
        $query->credentialID->equal($credentialID);
        return self::getEntityValue($query, $column);
    }

    /**
     * Sets a Credential Value
     * @param int              $credentialID
     * @param CredentialColumn $column
     * @param string|int       $value
     * @return bool
     */
    public static function setValue(
        int $credentialID,
        CredentialColumn $column,
        string|int $value,
    ): bool {
        return self::editEntityValue($credentialID, $column, $value);
    }



    /**
     * Creates a Hash and Salt (if required) for the the given Password
     * @param string $pass
     * @param string $salt Optional.
     * @return array{password:string,salt:string}
     */
    public static function createHash(string $pass, string $salt = ""): array {
        $salt = $salt !== "" ? $salt : Strings::random(50);
        $hash = base64_encode(hash_hmac("sha256", $pass, $salt, true));
        return [ "password" => $hash, "salt" => $salt ];
    }

    /**
     * Returns a parsed Name for the given Credential
     * @param mixed  $data
     * @param bool   $withEmail Optional.
     * @param string $prefix    Optional.
     * @return string
     */
    public static function getName(
        mixed $data,
        bool $withEmail = false,
        string $prefix = "",
    ): string {
        $id        = Strings::toString(Arrays::getValue($data, "credentialID", prefix: $prefix));
        $firstName = Strings::toString(Arrays::getValue($data, "firstName", prefix: $prefix));
        $lastName  = Strings::toString(Arrays::getValue($data, "lastName", prefix: $prefix));
        $email     = Strings::toString(Arrays::getValue($data, "email", prefix: $prefix));
        $result    = "";

        if ($firstName !== "" || $lastName !== "") {
            $result = Strings::merge($firstName, $lastName);
            if ($withEmail && $email !== "") {
                $result .= " ($email)";
            }
        }
        if ($result === "" && $id !== "") {
            $result = "#$id";
        }
        return $result;
    }

    /**
     * Returns a parsed Phone for the given Credential
     * @param mixed  $data
     * @param string $prefix   Optional.
     * @param bool   $withPlus Optional.
     * @return string
     */
    public static function getPhone(
        mixed $data,
        string $prefix = "",
        bool $withPlus = false,
    ): string {
        $phone     = Strings::toString(Arrays::getValue($data, "phone", prefix: $prefix));
        $cellphone = Strings::toString(Arrays::getValue($data, "cellphone", prefix: $prefix));
        $iddRoot   = Strings::toString(Arrays::getValue($data, "iddRoot", prefix: $prefix));

        if ($cellphone !== "" && $iddRoot !== "") {
            return ($withPlus ? "+" : "") . $iddRoot . $cellphone;
        }
        if ($cellphone !== "") {
            return $cellphone;
        }
        return $phone;
    }

    /**
     * Returns a WhatsApp url
     * @param mixed  $data
     * @param string $prefix Optional.
     * @return string
     */
    public static function getWhatsAppUrl(mixed $data, string $prefix = ""): string {
        $whatsapp = self::getPhone($data, $prefix, false);
        return Utils::getWhatsAppUrl($whatsapp);
    }
}
