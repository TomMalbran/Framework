<?php
namespace Framework\Auth;

use Framework\Request;
use Framework\System\StatusCode;
use Framework\NLS\NLS;
use Framework\File\FilePath;
use Framework\Database\Query;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;
use Framework\Utils\Utils;
use Framework\Schema\CredentialSchema;
use Framework\Schema\CredentialEntity;

use ArrayAccess;

/**
 * The Auth Credential
 */
class Credential extends CredentialSchema {

    /**
     * Creates a Access Query
     * @param string[]|string      $access
     * @param string[]|string|null $filter Optional.
     * @param mixed|integer        $value  Optional.
     * @return Query|null
     */
    private static function createAccessQuery(array|string $access, array|string|null $filter = null, mixed $value = 1): ?Query {
        $accesses = Arrays::toArray($access);
        if (empty($accesses)) {
            return null;
        }
        $query = Query::create("access", "IN", $accesses);
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
        return self::requestOne($query, $complete);
    }

    /**
     * Returns the Name of the Credential with the given ID
     * @param integer $credentialID
     * @return string
     */
    public static function getOneName(int $credentialID): string {
        $query      = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $credential = self::requestOne($query);
        return self::getName($credential);
    }

    /**
     * Returns the Credential with the given Email
     * @param string  $email
     * @param boolean $complete Optional.
     * @return CredentialEntity
     */
    public static function getByEmail(string $email, bool $complete = true): CredentialEntity {
        $query = Query::create("email", "=", $email);
        return self::requestOne($query, $complete);
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
        return self::requestOne($query, $complete);
    }



    /**
     * Returns true if there is an Credential with the given ID and Access(s)
     * @param integer              $credentialID
     * @param string[]|string      $access
     * @param string[]|string|null $filter       Optional.
     * @param mixed|integer        $value        Optional.
     * @return boolean
     */
    public static function existsWithAccess(int $credentialID, array|string $access, array|string|null $filter = null, mixed $value = 1): bool {
        $query = self::createAccessQuery($access, $filter, $value);
        if (empty($query)) {
            return false;
        }
        $query->add("CREDENTIAL_ID", "=", $credentialID);
        return self::entityExists($query);
    }

    /**
     * Returns true if there is an Credential with the given Email
     * @param string          $email
     * @param integer         $skipID Optional.
     * @param string[]|string $access Optional.
     * @return boolean
     */
    public static function emailExists(string $email, int $skipID = 0, array|string $access = ""): bool {
        $query = empty($access) ? new Query() : self::createAccessQuery($access);
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
     * @return array{}[]
     */
    public static function getAll(?Request $sort = null, ?Query $query = null): array {
        return self::request($query, false, $sort);
    }

    /**
     * Returns all the Credentials for the given Access(s)
     * @param string[]|string $access
     * @param Request|null    $sort   Optional.
     * @return array{}[]
     */
    public static function getAllForAccess(array|string $access, ?Request $sort = null): array {
        $query = self::createAccessQuery($access);
        if (empty($query)) {
            return [];
        }
        return self::request($query, false, $sort);
    }

    /**
     * Returns all the Credentials for the given IDs
     * @param integer[]    $credentialIDs
     * @param Request|null $sort          Optional.
     * @return array{}[]
     */
    public static function getAllWithIDs(array $credentialIDs, ?Request $sort = null): array {
        if (empty($credentialIDs)) {
            return [];
        }
        $query = Query::create("CREDENTIAL_ID", "IN", $credentialIDs);
        return self::request($query, false, $sort);
    }

    /**
     * Returns all the Credentials for the given Access(s) and filter
     * @param string[]|string $access
     * @param string          $filter
     * @param mixed|integer   $value  Optional.
     * @param Request|null    $sort   Optional.
     * @return array{}[]
     */
    public static function getAllWithFilter(array|string $access, string $filter, mixed $value = 1, ?Request $sort = null): array {
        $query = self::createAccessQuery($access, $filter, $value);
        if (empty($query)) {
            return [];
        }
        return self::request($query, false, $sort);
    }

    /**
     * Returns all the Credentials for the given Access(s)
     * @param string[]|string $access
     * @param Request|null    $sort   Optional.
     * @return array{}[]
     */
    public static function getActiveForAccess(array|string $access, ?Request $sort = null): array {
        $query = self::createAccessQuery($access);
        if (empty($query)) {
            return [];
        }
        $query->add("status", "=", StatusCode::Active);
        return self::request($query, false, $sort);
    }

    /**
     * Returns the latest Credentials for the given Access(s)
     * @param string[]|string $access
     * @param integer         $amount
     * @param string|null     $filter Optional.
     * @param mixed|integer   $value  Optional.
     * @return array{}[]
     */
    public static function getLatestForAccess(array|string $access, int $amount, ?string $filter = null, mixed $value = 1): array {
        $query = self::createAccessQuery($access, $filter, $value);
        if (empty($query)) {
            return [];
        }
        $query->orderBy("createdTime", false);
        $query->limit($amount);
        return self::request($query, false);
    }

    /**
     * Returns the create times for all the Credentials with the given access
     * @param integer $fromTime
     * @param string  $access   Optional.
     * @return array{}[]
     */
    public static function getAllCreateTimes(int $fromTime, string $access = ""): array {
        $query = Query::create("createdTime", ">=", $fromTime);
        $query->addIf("access", "=", $access);

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
     * @param string[]|string $access
     * @param string|null     $filter Optional.
     * @param mixed|integer   $value  Optional.
     * @return integer
     */
    public static function getTotalForAccess(array|string $access, ?string $filter = null, mixed $value = 1): int {
        $query = self::createAccessQuery($access, $filter, $value);
        if (empty($query)) {
            return 0;
        }
        return self::getEntityTotal($query);
    }

    /**
     * Requests data to the database
     * @param Query|null   $query    Optional.
     * @param boolean      $complete Optional.
     * @param Request|null $sort     Optional.
     * @return CredentialEntity[]
     */
    private static function request(?Query $query = null, bool $complete = false, ?Request $sort = null): array {
        $list   = self::getEntityList($query, $sort);
        $result = [];

        foreach ($list as $elem) {
            $elem->credentialName = self::getName($elem);

            if (!empty($elem->avatar)) {
                $elem->avatarFile = $elem->avatar;
                $elem->avatar     = FilePath::getUrl("avatars", $elem->avatar);
            }
            if (!$complete) {
                unset($elem->password);
                unset($elem->salt);
                unset($elem->accessToken);
                unset($elem->tokenExpiration);
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
    private static function requestOne(?Query $query = null, bool $complete = false): CredentialEntity {
        $list = self::request($query, $complete);
        return $list[0];
    }



    /**
     * Returns a select of all the Credentials
     * @return array{}[]
     */
    public static function getSelect(): array {
        return self::requestSelect();
    }

    /**
     * Returns a select of Credentials for the given Access(s)
     * @param string[]|string      $access
     * @param string[]|string|null $filter Optional.
     * @param mixed|integer        $value  Optional.
     * @return array{}[]
     */
    public static function getSelectForAccess(array|string $access, array|string|null $filter = null, mixed $value = 1): array {
        $query = self::createAccessQuery($access, $filter, $value);
        if (empty($query)) {
            return [];
        }
        $query->orderBy("access", false);
        return self::requestSelect($query);
    }

    /**
     * Returns a select of Credentials with the given IDs
     * @param integer[] $credentialIDs
     * @return array{}[]
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
     * @param string[]|string|null   $access       Optional.
     * @param integer[]|integer|null $credentialID Optional.
     * @param boolean                $splitText    Optional.
     * @return array{}[]
     */
    public static function search(string $text, int $amount = 10, array|string|null $access = null, array|int|null $credentialID = null, bool $splitText = true): array {
        $query = Query::createSearch([ "firstName", "lastName", "email" ], $text, "LIKE", true, $splitText);
        $query->addIf("access",         "IN", Arrays::toArray($access),        $access !== null);
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
     * @return array{}[]
     */
    private static function requestSelect(?Query $query = null): array {
        $list   = self::getEntityList($query);
        $result = [];

        foreach ($list as $elem) {
            $result[] = [
                "key"   => $elem->credentialID,
                "value" => self::getName($elem),
            ];
        }
        return $result;
    }

    /**
     * Returns a list of emails of the Credentials with the given Accesses
     * @param string[]|string      $access
     * @param string[]|string|null $filter Optional.
     * @param mixed|integer        $value  Optional.
     * @return array<string,string>
     */
    public static function getEmailsForAccess(array|string $access, array|string|null $filter = null, mixed $value = 1): array {
        $query = self::createAccessQuery($access, $filter, $value);
        if (empty($query)) {
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
        return self::getEntityValue($query, "reqPassChange") == 1;
    }



    /**
     * Creates a new Credential
     * @param Request|array{} $request
     * @param string          $access
     * @param boolean|null    $reqPassChange Optional.
     * @return integer
     */
    public static function create(Request|array $request, string $access, ?bool $reqPassChange = null): int {
        $fields = self::getFields($request, $access, $reqPassChange);
        $fields["lastLogin"]    = time();
        $fields["currentLogin"] = time();
        return self::createEntity($request, ...$fields);
    }

    /**
     * Edits the given Credential
     * @param integer         $credentialID
     * @param Request|array{} $request
     * @param string          $access        Optional.
     * @param boolean|null    $reqPassChange Optional.
     * @param boolean         $skipEmpty     Optional.
     * @return boolean
     */
    public static function edit(int $credentialID, Request|array $request, string $access = "", ?bool $reqPassChange = null, bool $skipEmpty = false): bool {
        $fields = self::getFields($request, $access, $reqPassChange);
        return self::schema()->edit($credentialID, $request, $fields, skipEmpty: $skipEmpty);
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
            status:           StatusCode::Inactive,
            observations:     "",
            sendEmails:       0,
            sendEmailNotis:   0,
            timezone:         0,
            currentLogin:     0,
            lastLogin:        0,
            askNotifications: 0,
            isDeleted:        1,
        );
    }

    /**
     * Parses the data and returns the fields
     * @param Request|array{} $request
     * @param string          $access        Optional.
     * @param boolean|null    $reqPassChange Optional.
     * @return array{}[]
     */
    private static function getFields(Request|array $request, string $access = "", ?bool $reqPassChange = null): array {
        $result = [];
        if (!empty($request["password"])) {
            $hash = self::createHash($request["password"]);
            $result["password"] = $hash["password"];
            $result["salt"]     = $hash["salt"];
        }
        if (!empty($access)) {
            $result["access"] = $access;
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
     * Sets the Credential Status
     * @param integer $credentialID
     * @param integer $status
     * @return boolean
     */
    public static function setStatus(int $credentialID, int $status): bool {
        return self::editEntity($credentialID, status: $status);
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
        $current = self::getValue($credentialID, "currentLogin");
        return self::editEntity(
            $credentialID,
            lastLogin:    $current,
            currentLogin: time(),
        );
    }

    /**
     * Gets a Credential Value
     * @param integer $credentialID
     * @param string  $key
     * @return mixed
     */
    public static function getValue(int $credentialID, string $key): mixed {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return self::getEntityValue($query, $key);
    }

    /**
     * Sets a Credential Value
     * @param integer $credentialID
     * @param string  $key
     * @param mixed   $value
     * @return boolean
     */
    public static function setValue(int $credentialID, string $key, mixed $value): bool {
        return self::editEntity($credentialID, ...[ $key => $value ]);
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
