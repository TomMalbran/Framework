<?php
namespace Framework\Auth;

use Framework\Request;
use Framework\Auth\Access;
use Framework\File\Path;
use Framework\Schema\Factory;
use Framework\Schema\Database;
use Framework\Schema\Model;
use Framework\Schema\Query;
use Framework\Utils\Utils;

/**
 * The Auth Credential
 */
class Credential {
    
    private static $loaded = false;
    private static $schema = null;
    
    
    /**
     * Loads the Credential Schema
     * @return Schema
     */
    public static function getSchema() {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("credentials");
        }
        return self::$schema;
    }
    
    
    
    /**
     * Returns the Credential with the given ID
     * @param string  $credentialID
     * @param boolean $complete     Optional.
     * @return Model
     */
    public static function getOne($credentialID, $complete = false) {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return self::requestOne($query, $complete);
    }
    
    /**
     * Returns the Credential with the given Email
     * @param string  $email
     * @param boolean $complete Optional.
     * @return Model
     */
    public static function getByEmail($email, $complete = true) {
        $query = Query::create("email", "=", $email);
        return self::requestOne($query, $complete);
    }



    /**
     * Returns true if there is an Credential with the given ID
     * @param integer $crendentialID
     * @return boolean
     */
    public static function exists($crendentialID) {
        return self::getSchema()->exists($crendentialID);
    }
    
    /**
     * Returns true if there is an Credential with the given ID and Level(s)
     * @param integer           $crendentialID
     * @param integer|integer[] $level
     * @return boolean
     */
    public static function existsWithLevel($crendentialID, $level) {
        $levels = Utils::toArray($level);
        if (empty($levels)) {
            return false;
        }
        $query = Query::create("level", "IN", $levels);
        return self::getSchema()->exists($crendentialID, $query);
    }
    
    /**
     * Returns true if there is an Credential with the given Email
     * @param string  $email
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function emailExists($email, $skipID = 0) {
        $query = Query::create("email", "=", $email);
        $query->addIf("CREDENTIAL_ID", "<>", $skipID);
        return self::getSchema()->exists($query);
    }

    /**
     * Returns true if there is an Credential with the given DNI
     * @param string  $dni
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function dniExists($dni, $skipID = 0) {
        $query = Query::create("dni", "=", $dni);
        $query->addIf("CREDENTIAL_ID", "<>", $skipID);
        return self::getSchema()->exists($query);
    }

    /**
     * Returns true if there is an Credential with the given CUIT
     * @param string  $cuit
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function cuitExists($cuit, $skipID = 0) {
        $query = Query::create("cuit", "=", $cuit);
        $query->addIf("CREDENTIAL_ID", "<>", $skipID);
        return self::getSchema()->exists($query);
    }
    
    
    
    /**
     * Returns all the Credentials for the given Level(s)
     * @param integer[]|integer $level
     * @param Request           $sort  Optional.
     * @return array
     */
    public static function getAllForLevel($level, Request $sort = null) {
        $levels = Utils::toArray($level);
        if (empty($levels)) {
            return [];
        }
        $query = Query::create("level", "IN", $levels);
        return self::request($query, false, $sort);
    }

    /**
     * Returns the total amount of Credentials for the given Level(s)
     * @param integer[]|integer $level
     * @return integer
     */
    public static function getTotalForLevel($level) {
        $levels = Utils::toArray($level);
        if (empty($levels)) {
            return 0;
        }
        $query = Query::create("level", "IN", $levels);
        return self::getSchema()->getTotal($query);
    }
    
    /**
     * Requests data to the database
     * @param Query   $query    Optional.
     * @param boolean $complete Optional.
     * @param Request $sort     Optional.
     * @return array
     */
    private static function request(Query $query = null, $complete = false, Request $sort = null) {
        $request = self::getSchema()->getAll($query, $sort);
        $result  = [];
        
        foreach ($request as $row) {
            $fields = $row;
            $fields["credentialName"] = Utils::createRealName($row);

            if (!empty($row["avatar"]) && Path::exists("avatars", $row["avatar"])) {
                $fields["avatar"] = Path::getUrl("avatars", $row["avatar"]);
            }
            if (!$complete) {
                unset($fields["password"]);
                unset($fields["salt"]);
            }
            $result[] = $fields;
        }
        return $result;
    }
    
    /**
     * Requests a single row from the database
     * @param Query   $query    Optional.
     * @param boolean $complete Optional.
     * @return Model
     */
    private static function requestOne(Query $query = null, $complete = false) {
        $request = self::request($query, $complete);
        return self::getSchema()->getModel($request);
    }
    
    
    
    /**
     * Returns a select of all the Credentials
     * @return array
     */
    public static function getSelect() {
        return self::requestSelect();
    }
    
    /**
     * Returns a select of Credentials for the given Level(s)
     * @param integer[]|integer $level
     * @return array
     */
    public static function getSelectForLevel($level) {
        $levels = Utils::toArray($level);
        if (empty($levels)) {
            return [];
        }
        $query = Query::create("level", "IN", $levels);
        $query->orderBy("level", false);
        return self::requestSelect($query);
    }
    
    /**
     * Returns a select of Credentials with the given IDs
     * @param integer[] $credentialIDs
     * @return array
     */
    public static function getSelectForIDs(array $credentialIDs) {
        if (empty($credentialIDs)) {
            return [];
        }
        $query = Query::create("CREDENTIAL_ID", "IN", $credentialIDs);
        $query->orderBy("firstName", true);
        return self::requestSelect($query);
    }
    
    /**
     * Returns the Credentials  that contains the text and the given Levels
     * @param string    $text
     * @param integer   $amount        Optional.
     * @param integer[] $levels        Optional.
     * @param integer[] $credentialIDs Optional.
     * @return array
     */
    public static function search($text, $amount = 10, array $levels = null, array $credentialIDs = null) {
        $query = Query::createSearch([ "firstName", "lastName", "email", "phone" ], $text);
        $query->addIf("level",         "IN", $levels);
        $query->addIf("CREDENTIAL_ID", "IN", $credentialIDs);
        $query->limit($amount);
        
        $request = self::requestSelect($query);
        $result  = [];
        
        foreach ($request as $row) {
            $result[] = [
                "id"    => $row["key"],
                "title" => $row["value"],
            ];
        }
        return $result;
    }

    /**
     * Returns a select of Credentials under the given conditions
     * @param Query $query Optional.
     * @return array
     */
    private static function requestSelect(Query $query = null) {
        $request = self::getSchema()->getMap($query);
        $result  = [];
        
        foreach ($request as $row) {
            $result[] = [
                "key"   => $row["credentialID"],
                "value" => Utils::createRealName($row),
            ];
        }
        return $result;
    }

    /**
     * Returns a list of emails of the Credentials with the given Levels
     * @param integer[]|integer $level
     * @param string[]|string   $filter Optional.
     * @return array
     */
    public static function getEmailsForLevel($level, $filter = null) {
        $levels = Utils::toArray($level);
        if (empty($levels)) {
            return [];
        }
        $query = Query::create("level", "IN", $levels);
        if (!empty($filter)) {
            $filters = Utils::toArray($filter);
            foreach ($filters as $key) {
                $query->add($key, "=", 1);
            }
        }
        return self::getSchema()->getColumn($query, "email");
    }
    
    
    
    /**
     * Returns true if the given password is correct for the given Credential ID
     * @param Model  $credential
     * @param string $password
     * @return boolean
     */
    public static function isPasswordCorrect(Model $credential, $password) {
        $hash = Utils::createHash($password, $credential->salt);
        return $hash["password"] == $credential->password;
    }
    
    /**
     * Returns true if the given Credential requires a password change
     * @param integer $credentialID
     * @param string  $email        Optional.
     * @return boolean
     */
    public static function reqPassChange($credentialID, $email = null) {
        if (empty($credentialID) && empty($email)) {
            return false;
        }
        $query = new Query();
        $query->startOr();
        $query->addIf("CREDENTIAL_ID", "=", $credentialID);
        $query->addIf("email",         "=", $email);
        $query->endOr();
        return self::getSchema()->getValue($query, "reqPassChange") == 1;
    }
    
    
    
    /**
     * Creates a new Credential
     * @param Request $request
     * @param integer $level
     * @param boolean $reqPassChange Optional.
     * @return integer
     */
    public static function create(Request $request, $level, $reqPassChange = null) {
        $fields = self::getFields($request, $level, $reqPassChange);
        return self::getSchema()->create($request, $fields + [
            "lastLogin"    => time(),
            "currentLogin" => time(),
        ]);
    }
    
    /**
     * Edits the given Credential
     * @param integer $credentialID
     * @param Request $request
     * @param integer $level         Optional.
     * @param boolean $reqPassChange Optional.
     * @return void
     */
    public static function edit($credentialID, Request $request, $level = 0, $reqPassChange = null) {
        $fields = self::getFields($request, $level, $reqPassChange);
        self::getSchema()->edit($credentialID, $request, $fields);
    }
    
    /**
     * Parses the data and returns the fields
     * @param Request $request
     * @param integer $level
     * @param boolean $reqPassChange
     * @return array
     */
    private static function getFields(Request $request, $level, $reqPassChange) {
        $result = [];
        if ($request->has("password")) {
            $hash = Utils::createHash($request->password);
            $result["password"] = $hash["password"];
            $result["salt"]     = $hash["salt"];
        }
        if (!empty($level)) {
            $result["level"] = $level;
        }
        if ($reqPassChange !== null) {
            $result["reqPassChange"] = $reqPassChange ? 1 : 0;
        }
        return $result;
    }
    
    /**
     * Deletes the given Credential
     * @param integer $credentialID
     * @return boolean
     */
    public static function delete($credentialID) {
        return self::getSchema()->delete($credentialID);
    }
    
    
    
    /**
     * Sets the current User for the given Credential
     * @param integer $credentialID
     * @param integer $userID
     * @return void
     */
    public static function setCurrentUser($credentialID, $userID) {
        self::getSchema()->edit($credentialID, [
            "currentUser" => $userID,
        ]);
    }

    /**
     * Updates the login time for the given Credential
     * @param integer $credentialID
     * @return void
     */
    public static function updateLoginTime($credentialID) {
        $query   = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $current = self::getSchema()->getValue($query, "currentLogin");
        self::getSchema()->edit($credentialID, [
            "lastLogin"    => $current,
            "currentLogin" => time(),
        ]);
    }
    
    /**
     * Sets the Credential password
     * @param integer $credentialID
     * @param string  $password
     * @return array
     */
    public static function setPassword($credentialID, $password) {
        $hash = Utils::createHash($password);
        self::getSchema()->edit($credentialID, [
            "password" => $hash["password"],
            "salt"     => $hash["salt"],
        ]);
        return $hash;
    }
    
    /**
     * Sets the require password change for the given Credential
     * @param integer $credentialID
     * @param boolean $require
     * @return void
     */
    public static function setReqPassChange($credentialID, $require) {
        self::getSchema()->edit($credentialID, [
            "reqPassChange" => $require ? 1 : 0,
        ]);
    }

    /**
     * Sets the Credential avatar
     * @param integer $credentialID
     * @param string  $avatar
     * @return void
     */
    public static function setAvatar($credentialID, $avatar) {
        self::getSchema()->edit($credentialID, [
            "avatar" => $avatar,
        ]);
    }



    /**
     * Seeds the Owner
     * @param Database $db
     * @param string   $firstName
     * @param string   $lastName
     * @param string   $email
     * @param string   $password
     * @return void
     */
    public static function seedOwner(Database $db, $firstName, $lastName, $email, $password) {
        if ($db->hasTable("credentials")) {
            $hash = Utils::createHash($password);
            $db->insert("credentials", [
                "firstName"    => $firstName,
                "lastName"     => $lastName,
                "email"        => $email,
                "password"     => $hash["password"],
                "salt"         => $hash["salt"],
                "language"     => "es",
                "level"        => Access::Owner(),
                "lastLogin"    => time(),
                "currentLogin" => time(),
                "createdTime"  => time(),
            ]);
            print("<i>Owner Created</i><br>");
        }
    }
}
