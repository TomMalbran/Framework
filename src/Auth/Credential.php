<?php
namespace Framework\Auth;

use Framework\Request;
use Framework\Core\NLS;
use Framework\File\FilePath;
use Framework\Database\Query;
use Framework\System\Access;
use Framework\System\Status;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;
use Framework\Utils\Utils;
use Framework\Schema\CredentialSchema;
use Framework\Schema\CredentialEntity;
use Framework\Schema\CredentialColumn;

use ArrayAccess;

/**
 * The Auth Credential
 */
class Credential extends CredentialSchema {

    /**
     * Creates a Access Query
     * @param Access[]|Access|null $accessName
     * @param string[]|string|null $filter     Optional.
     * @param mixed|integer        $value      Optional.
     * @return Query
     */
    private static function createAccessQuery(array|Access|null $accessName, array|string|null $filter = null, mixed $value = 1): Query {
        $accessNames = Access::toStrings($accessName);
        if (empty($accessNames)) {
            return new Query();
        }

        $query = Query::create("access", "IN", $accessNames);
        if (!empty($filter)) {
            $filters = Arrays::toArray($filter);
            foreach ($filters as $key) {
                $query->add($key, "=", $value);
            }
        }
        return $query;
    }



    /**
     * Returns the Credential with the given ID
     * @param integer $credentialID
     * @param boolean $complete     Optional.
     * @return CredentialEntity
     */
    public static function getByID(int $credentialID, bool $complete = false): CredentialEntity {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return self::getCredential($query, $complete);
    }

    /**
     * Returns the Credential with the given Email
     * @param string  $email
     * @param boolean $complete Optional.
     * @return CredentialEntity
     */
    public static function getByEmail(string $email, bool $complete = true): CredentialEntity {
        $query = Query::create("email", "=", $email);
        return self::getCredential($query, $complete);
    }

    /**
     * Returns the Credential with the given Access Token
     * @param string  $accessToken
     * @param boolean $complete    Optional.
     * @return CredentialEntity
     */
    public static function getByAccessToken(string $accessToken, bool $complete = true): CredentialEntity {
        $query = Query::create("accessToken", "=", $accessToken);
        $query->add("tokenExpiration", ">", time());
        return self::getCredential($query, $complete);
    }



    /**
     * Returns true if there is an Credential with the given ID and Access(s)
     * @param integer              $credentialID
     * @param Access[]|Access      $accessName
     * @param string[]|string|null $filter       Optional.
     * @param mixed|integer        $value        Optional.
     * @return boolean
     */
    public static function existsWithAccess(int $credentialID, array|Access $accessName, array|string|null $filter = null, mixed $value = 1): bool {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return false;
        }

        $query->add("CREDENTIAL_ID", "=", $credentialID);
        return self::entityExists($query);
    }

    /**
     * Returns true if there is an Credential with the given Email
     * @param string               $email
     * @param integer              $skipID     Optional.
     * @param Access[]|Access|null $accessName Optional.
     * @return boolean
     */
    public static function emailExists(string $email, int $skipID = 0, array|Access|null $accessName = null): bool {
        $query = self::createAccessQuery($accessName);
        $query->add("email", "=", $email);
        $query->addIf("CREDENTIAL_ID", "<>", $skipID);
        return self::entityExists($query);
    }

    /**
     * Returns true if there is an Credential with the given Value for the given Field
     * @param string  $field
     * @param mixed   $value
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function fieldExists(string $field, mixed $value, int $skipID = 0): bool {
        $query = Query::create($field, "=", $value);
        $query->addIf("CREDENTIAL_ID", "<>", $skipID);
        return self::entityExists($query);
    }



    /**
     * Returns all the Credentials
     * @param Request|null $sort  Optional.
     * @param Query|null   $query Optional.
     * @return CredentialEntity[]
     */
    public static function getAll(?Request $sort = null, ?Query $query = null): array {
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
     * @param integer[]    $credentialIDs
     * @param Request|null $sort          Optional.
     * @return CredentialEntity[]
     */
    public static function getAllWithIDs(array $credentialIDs, ?Request $sort = null): array {
        if (empty($credentialIDs)) {
            return [];
        }
        $query = Query::create("CREDENTIAL_ID", "IN", $credentialIDs);
        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns all the Credentials for the given Access(s) and filter
     * @param Access[]|Access $accessName
     * @param string          $filter
     * @param mixed|integer   $value      Optional.
     * @param Request|null    $sort       Optional.
     * @return CredentialEntity[]
     */
    public static function getAllWithFilter(array|Access $accessName, string $filter, mixed $value = 1, ?Request $sort = null): array {
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
    public static function getActiveForAccess(array|Access $accessName, ?Request $sort = null): array {
        $query = self::createAccessQuery($accessName);
        if ($query->isEmpty()) {
            return [];
        }

        $query->add("status", "=", Status::Active->name);
        return self::getCredentials($query, $sort, false);
    }

    /**
     * Returns the latest Credentials for the given Access(s)
     * @param Access[]|Access $accessName
     * @param integer         $amount
     * @param string|null     $filter     Optional.
     * @param mixed|integer   $value      Optional.
     * @return CredentialEntity[]
     */
    public static function getLatestForAccess(array|Access $accessName, int $amount, ?string $filter = null, mixed $value = 1): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        $query->orderBy("createdTime", false);
        $query->limit($amount);
        return self::getCredentials($query, null, false);
    }

    /**
     * Returns the create times for all the Credentials with the given Access
     * @param integer     $fromTime
     * @param Access|null $accessName Optional.
     * @return array{}[]
     */
    public static function getAllCreateTimes(int $fromTime, ?Access $accessName = null): array {
        $query = self::createAccessQuery($accessName);
        $query->add("createdTime", ">=", $fromTime);

        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            $result[$elem->credentialID] = $elem->createdTime;
        }
        return $result;
    }

    /**
     * Returns the total amount of Credentials
     * @param Query|null $query Optional.
     * @return integer
     */
    public static function getTotal(?Query $query = null): int {
        return self::getEntityTotal($query);
    }

    /**
     * Returns the total amount of Credentials for the given Access(s)
     * @param Access[]|Access $accessName
     * @param string|null     $filter     Optional.
     * @param mixed|integer   $value      Optional.
     * @return integer
     */
    public static function getTotalForAccess(array|Access $accessName, ?string $filter = null, mixed $value = 1): int {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return 0;
        }

        return self::getEntityTotal($query);
    }

    /**
     * Returns all the Credentials for the given Query
     * @param Query|null   $query    Optional.
     * @param Request|null $sort     Optional.
     * @param boolean      $complete Optional.
     * @return CredentialEntity[]
     */
    private static function getCredentials(?Query $query = null, ?Request $sort = null, bool $complete = false): array {
        $list   = self::getEntityList($query, $sort);
        $result = [];

        foreach ($list as $elem) {
            $elem->credentialName = self::getName($elem);
            $elem->accessName     = Access::getName($elem->access);

            if (!empty($elem->avatar)) {
                $elem->avatarFile = $elem->avatar;
                $elem->avatar     = FilePath::getUrl("avatars", $elem->avatar);
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
     * @param Query|null $query    Optional.
     * @param boolean    $complete Optional.
     * @return CredentialEntity
     */
    private static function getCredential(?Query $query = null, bool $complete = false): CredentialEntity {
        $list = self::getCredentials($query, null, $complete);
        if (empty($list)) {
            return new CredentialEntity();
        }
        return $list[0];
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
     * @param mixed|integer        $value      Optional.
     * @return Select[]
     */
    public static function getSelectForAccess(array|Access $accessName, array|string|null $filter = null, mixed $value = 1): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        $query->orderBy("access", false);
        return self::requestSelect($query);
    }

    /**
     * Returns a select of Credentials with the given IDs
     * @param integer[] $credentialIDs
     * @return Select[]
     */
    public static function getSelectForIDs(array $credentialIDs): array {
        if (empty($credentialIDs)) {
            return [];
        }
        $query = Query::create("CREDENTIAL_ID", "IN", $credentialIDs);
        $query->orderBy("firstName", true);
        return self::requestSelect($query);
    }

    /**
     * Returns the Credentials that contains the text and the given Accesses
     * @param string                 $text
     * @param integer                $amount       Optional.
     * @param Access[]|Access|null   $accessName   Optional.
     * @param integer[]|integer|null $credentialID Optional.
     * @param boolean                $splitText    Optional.
     * @return array{}[]
     */
    public static function search(string $text, int $amount = 10, array|Access|null $accessName = null, array|int|null $credentialID = null, bool $splitText = true): array {
        $query = self::createAccessQuery($accessName);
        $query->search([ "firstName", "lastName", "email" ], $text, "LIKE", true, $splitText);
        $query->addIf("CREDENTIAL_ID", "IN", Arrays::toArray($credentialID), $credentialID !== null);
        $query->limit($amount);

        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            $result[] = [
                "id"    => $elem->credentialID,
                "title" => self::getName($elem),
                "email" => $elem->email,
            ];
        }
        return $result;
    }

    /**
     * Returns a select of Credentials under the given conditions
     * @param Query|null $query Optional.
     * @return Select[]
     */
    private static function requestSelect(?Query $query = null): array {
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
     * @param mixed|integer        $value      Optional.
     * @return array<string,string>
     */
    public static function getEmailsForAccess(array|Access $accessName, array|string|null $filter = null, mixed $value = 1): array {
        $query = self::createAccessQuery($accessName, $filter, $value);
        if ($query->isEmpty()) {
            return [];
        }

        $list = self::getEntityList($query);
        return Arrays::createMap($list, "email", "language");
    }



    /**
     * Returns true if the given password is correct for the given Credential ID
     * @param CredentialEntity|integer $credential
     * @param string                   $password
     * @return boolean
     */
    public static function isPasswordCorrect(CredentialEntity|int $credential, string $password): bool {
        if (!($credential instanceof CredentialEntity)) {
            $credential = self::getByID($credential, true);
        }
        if ($credential->passExpiration > 0 && $credential->passExpiration < time()) {
            return false;
        }
        $hash = self::createHash($password, $credential->salt);
        return $hash["password"] == $credential->password;
    }

    /**
     * Returns true if the given Credential requires a password change
     * @param integer $credentialID Optional.
     * @param string  $email        Optional.
     * @return boolean
     */
    public static function reqPassChange(int $credentialID = 0, string $email = ""): bool {
        if (empty($credentialID) && empty($email)) {
            return false;
        }
        $query = new Query();
        $query->startOr();
        $query->addIf("CREDENTIAL_ID", "=", $credentialID);
        $query->addIf("email",         "=", $email);
        $query->endOr();
        return self::getEntityValue($query, CredentialColumn::ReqPassChange) == 1;
    }



    /**
     * Creates a new Credential
     * @param Request|array{} $request
     * @param Access          $accessName
     * @param boolean|null    $reqPassChange Optional.
     * @return integer
     */
    public static function create(Request|array $request, Access $accessName, ?bool $reqPassChange = null): int {
        $fields = self::getFields($request, $accessName, $reqPassChange);
        $fields["lastLogin"]    = time();
        $fields["currentLogin"] = time();
        return self::createEntity($request, ...$fields);
    }

    /**
     * Edits the given Credential
     * @param integer         $credentialID
     * @param Request|array{} $request
     * @param Access|null     $accessName    Optional.
     * @param boolean|null    $reqPassChange Optional.
     * @param boolean         $skipEmpty     Optional.
     * @return boolean
     */
    public static function edit(int $credentialID, Request|array $request, ?Access $accessName = null, ?bool $reqPassChange = null, bool $skipEmpty = false): bool {
        $fields = self::getFields($request, $accessName, $reqPassChange);
        return self::editEntityData($credentialID, $request, $fields, skipEmpty: $skipEmpty);
    }

    /**
     * Updates the given Credential
     * @param integer   $credentialID
     * @param array{}[] $fields
     * @return boolean
     */
    public static function update(int $credentialID, array $fields): bool {
        return self::editEntity($credentialID, ...$fields);
    }

    /**
     * Deletes the given Credential
     * @param integer $credentialID
     * @return boolean
     */
    public static function delete(int $credentialID): bool {
        return self::deleteEntity($credentialID);
    }

    /**
     * Destroys the Credential
     * @param integer $credentialID
     * @return boolean
     */
    public static function destroy(int $credentialID): bool {
        return self::editEntity(
            $credentialID,
            currentUser:      0,
            email:            "mail@mail.com",
            firstName:        NLS::get("GENERAL_NAME"),
            lastName:         NLS::get("GENERAL_LAST_NAME"),
            phone:            "",
            language:         "",
            avatar:           "",
            password:         "",
            salt:             "",
            reqPassChange:    0,
            passExpiration:   0,
            accessToken:      "",
            tokenExpiration:  0,
            observations:     "",
            sendEmails:       0,
            sendEmailNotis:   0,
            timezone:         0,
            currentLogin:     0,
            lastLogin:        0,
            askNotifications: 0,
            status:           Status::Inactive,
            isDeleted:        1,
        );
    }

    /**
     * Parses the data and returns the fields
     * @param Request|array{} $request
     * @param Access|null     $accessName    Optional.
     * @param boolean|null    $reqPassChange Optional.
     * @return array{}[]
     */
    private static function getFields(Request|array $request, ?Access $accessName = null, ?bool $reqPassChange = null): array {
        $result = [];
        if (!empty($request["password"])) {
            $hash = self::createHash($request["password"]);
            $result["password"] = $hash["password"];
            $result["salt"]     = $hash["salt"];
        }
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
     * @param integer $credentialID
     * @param integer $userID
     * @return boolean
     */
    public static function setCurrentUser(int $credentialID, int $userID): bool {
        return self::editEntity($credentialID, currentUser: $userID);
    }

    /**
     * Sets the Credential Access
     * @param integer $credentialID
     * @param string  $access
     * @return boolean
     */
    public static function setAccess(int $credentialID, string $access): bool {
        return self::editEntity($credentialID, access: $access);
    }

    /**
     * Sets the Credential Email
     * @param integer $credentialID
     * @param string  $email
     * @return boolean
     */
    public static function setEmail(int $credentialID, string $email): bool {
        return self::editEntity($credentialID, email: $email);
    }

    /**
     * Updates the Language for the given Credential
     * @param integer $credentialID
     * @param string  $language
     * @return boolean
     */
    public static function setLanguage(int $credentialID, string $language): bool {
        $language = Strings::substringBefore($language, "-");
        return self::editEntity($credentialID, language: $language);
    }

    /**
     * Updates the Timezone for the given Credential
     * @param integer $credentialID
     * @param integer $timezone
     * @return boolean
     */
    public static function setTimezone(int $credentialID, int $timezone): bool {
        return self::editEntity($credentialID, timezone: $timezone);
    }

    /**
     * Sets the Credential Avatar
     * @param integer $credentialID
     * @param string  $avatar
     * @return boolean
     */
    public static function setAvatar(int $credentialID, string $avatar): bool {
        return self::editEntity($credentialID, avatar: $avatar);
    }

    /**
     * Sets the Credential Appearance
     * @param integer $credentialID
     * @param string  $appearance
     * @return boolean
     */
    public static function setAppearance(int $credentialID, string $appearance): bool {
        return self::editEntity($credentialID, appearance: $appearance);
    }

    /**
     * Sets the Credential Password
     * @param integer $credentialID
     * @param string  $password
     * @return array{}[]
     */
    public static function setPassword(int $credentialID, string $password): array {
        $hash = self::createHash($password);
        self::editEntity(
            $credentialID,
            password:       $hash["password"],
            salt:           $hash["salt"],
            passExpiration: 0,
            reqPassChange:  0,
        );
        return $hash;
    }

    /**
     * Sets the require password change for the given Credential
     * @param integer $credentialID
     * @param boolean $reqPassChange
     * @return boolean
     */
    public static function setReqPassChange(int $credentialID, bool $reqPassChange): bool {
        return self::editEntity($credentialID, reqPassChange: $reqPassChange);
    }

    /**
     * Sets a temporary Password to the given Credential
     * @param integer $credentialID
     * @param integer $hours        Optional.
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
     * @param integer $credentialID
     * @param integer $hours        Optional.
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
     * @param integer $credentialID
     * @return boolean
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
     * @param integer $credentialID
     * @return boolean
     */
    public static function dontAskNotifications(int $credentialID): bool {
        return self::editEntity($credentialID, askNotifications: false);
    }

    /**
     * Updates the login time for the given Credential
     * @param integer $credentialID
     * @return boolean
     */
    public static function updateLoginTime(int $credentialID): bool {
        $current = self::getValue($credentialID, CredentialColumn::CurrentLogin);
        return self::editEntity(
            $credentialID,
            lastLogin:    $current,
            currentLogin: time(),
        );
    }

    /**
     * Sets the Credential Progress
     * @param integer $credentialID
     * @param integer $value
     * @return boolean
     */
    public static function setProgress(int $credentialID, int $value): bool {
        return self::editEntity($credentialID, progressValue: $value);
    }

    /**
     * Sets the Credential Status
     * @param integer $credentialID
     * @param Status  $status
     * @return boolean
     */
    public static function setStatus(int $credentialID, Status $status): bool {
        return self::editEntity($credentialID, status: $status);
    }

    /**
     * Gets a Credential Value
     * @param integer          $credentialID
     * @param CredentialColumn $column
     * @return mixed
     */
    public static function getValue(int $credentialID, CredentialColumn $column): mixed {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return self::getEntityValue($query, $column);
    }

    /**
     * Sets a Credential Value
     * @param integer          $credentialID
     * @param CredentialColumn $column
     * @param mixed            $value
     * @return boolean
     */
    public static function setValue(int $credentialID, CredentialColumn $column, mixed $value): bool {
        return self::editEntity($credentialID, ...[ $column->base() => $value ]);
    }



    /**
     * Creates a Hash and Salt (if required) for the the given Password
     * @param string $pass
     * @param string $salt Optional.
     * @return array{}
     */
    public static function createHash(string $pass, string $salt = ""): array {
        $salt = !empty($salt) ? $salt : Strings::random(50);
        $hash = base64_encode(hash_hmac("sha256", $pass, $salt, true));
        return [ "password" => $hash, "salt" => $salt ];
    }

    /**
     * Returns a parsed Name for the given Credential
     * @param ArrayAccess|array{} $data
     * @param string              $prefix Optional.
     * @return string
     */
    public static function getName(ArrayAccess|array $data, string $prefix = ""): string {
        $id        = Arrays::getValue($data, "credentialID", "", $prefix);
        $firstName = Arrays::getValue($data, "firstName",    "", $prefix);
        $lastName  = Arrays::getValue($data, "lastName",     "", $prefix);
        $result    = "";

        if (!empty($firstName) && !empty($lastName)) {
            $result = "$firstName $lastName";
        }
        if (empty($result) && !empty($id)) {
            $result = "#$id";
        }
        return $result;
    }

    /**
     * Returns a parsed Phone for the given Credential
     * @param ArrayAccess|array{} $data
     * @param string              $prefix   Optional.
     * @param boolean             $withPlus Optional.
     * @return string
     */
    public static function getPhone(ArrayAccess|array $data, string $prefix = "", bool $withPlus = false): string {
        $phone     = Arrays::getValue($data, "phone",     "", $prefix);
        $cellphone = Arrays::getValue($data, "cellphone", "", $prefix);
        $iddRoot   = Arrays::getValue($data, "iddRoot",   "", $prefix);

        if (!empty($cellphone) && !empty($iddRoot)) {
            return ($withPlus ? "+" : "") . $iddRoot . $cellphone;
        }
        if (!empty($cellphone)) {
            return $cellphone;
        }
        return $phone;
    }

    /**
     * Returns a WhatsApp url
     * @param ArrayAccess|array{} $data
     * @param string              $prefix Optional.
     * @return string
     */
    public static function getWhatsAppUrl(ArrayAccess|array $data, string $prefix = ""): string {
        $whatsapp = self::getPhone($data, $prefix, false);
        return Utils::getWhatsAppUrl($whatsapp);
    }
}
