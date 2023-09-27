<?php

namespace SqlAccess;

include_once "conn_info.php";

use mysqli;
use function ConnectionInfo\get_host;
use function ConnectionInfo\get_user;
use function ConnectionInfo\get_pass;
use function ConnectionInfo\get_picks_db_name;

class SqlAccessController
{
    private mysqli $sql_conn;
    private mysqli $picks_db_conn;
    protected $construct_error = null;
    private string $log_file;

    private const USER_TABLE = "Users";
    private const PICKS_TABLE = "Picks";
    /*
    * Constructor. Establishes connection with SQL, and creates DB, connects to DB
    * and creates two tables (Users and Picks). Should gracefully only create when
    * needed, and always establish connection.
    *
    * Check SqlAccessController->$construct_error == null to check success
    */
    public function __construct()
    {
        $this->log_file = "./sql_interface_errors.log";

        $this->sql_conn = new mysqli(get_host(), get_user(), get_pass());

        if ($this->sql_conn->connect_error) {
            error_log("Could not establish connection with SQL\n", 3, $this->log_file);
        } else if (!$this->initialize_pick_database(get_picks_db_name())) {
            $this->construct_error = "DB Init error";
            $this->sql_conn->close();
        } else if (!$this->establish_connection_to_db(get_picks_db_name())) {
            $this->construct_error = "DB connection error";
            $this->sql_conn->close();
        } else if (!$this->create_tables(self::USER_TABLE, self::PICKS_TABLE)) {
            $this->construct_error = "Table creation error";
            $this->sql_conn->close();
        }
    }

    /*
    * Internal
    * Happens on creation of this object. Creates the database if it does
    * not exist, and returns a boolean indicating the success of creation
    */
    private function initialize_pick_database(string $db_name): bool
    {
        $create_db_cmd = "CREATE DATABASE IF NOT EXISTS " . $db_name;
        if ($this->sql_conn->query($create_db_cmd) === TRUE) {
            return true;
        } else {
            error_log("Could not create database\n", 3, $this->log_file);
            return false;
        }
    }

    /*
    * Internal
    * Populates the Database Connection object upon creation of the controller
    * Returns a boolean indicating the success of connection 
    * */
    private function establish_connection_to_db(string $db_name): bool
    {
        $this->picks_db_conn = new mysqli(
            get_host(),
            get_user(),
            get_pass(),
            $db_name
        );
        if (!$this->picks_db_conn) {
            error_log("Could not establish connection with db\n", 3, $this->log_file);
            return false;
        } else {
            return true;
        }
    }

    /*
    * Returns string containing most recent connection error
    * Empty string = no error
    */
    public function check_connection_error()
    {
        return $this->construct_error;
    }

    /*
    * Internal:
    * Creates the users and picks tables in the database upon creation of the
    * controller. Returns boolean indicating the success of creation
    */
    private function create_tables(string $users, string $picks): bool
    {
        $create_user_table = "CREATE TABLE IF NOT EXISTS " . $users . "( 
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
            name VARCHAR(30),
            username VARCHAR(30), 
            pin INT)";
        $create_picks_table = "CREATE TABLE IF NOT EXISTS " . $picks . "(
            pickid INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(30),
            week_number INT,
            pick VARCHAR(30))";
        if ($this->picks_db_conn->query($create_user_table) !== TRUE) {
            error_log("Could not create table " . $users, 3, $this->log_file);
            return false;
        } else if ($this->picks_db_conn->query($create_picks_table) !== TRUE) {
            error_log("Could not create table " . $picks, 3, $this->log_file);
            return false;
        } else {
            return true;
        }
    }

    /*
    * Returns array of usernames as strings 
    */
    public function get_user_table(): array
    {
        $get_all_cmd = "SELECT username FROM " . self::USER_TABLE;
        $result = $this->picks_db_conn->query($get_all_cmd);
        $array_of_users = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $array_of_users[] = $row['username'];
            }
        }
        return $array_of_users;
    }

    /*
    * Currently only used for debugging. 
    * Returns an array of picks. Each pick is an associative array
    * key: value type
    * 'pickid': int
    * 'username': string
    * 'week_number': int
    * 'pick': string
    */
    public function get_pick_table(): array
    {
        $get_all_cmd = "SELECT * FROM " . self::PICKS_TABLE;
        $result = $this->picks_db_conn->query($get_all_cmd);
        $array_of_picks = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                array_push($array_of_picks, $row);
            }
        }
        return $array_of_picks;
    }

    /*
    * Adds user to Database
    * Returns success code int
    *  0 : Success
    *  1 : Sql Creation error
    *  4 : User already exists
    */
    public function add_user(string $name, string $new_user, int $new_pin): int
    {
        if ($this->user_exists($new_user)) {
            return 4;
        }
        $create_user_cmd = "INSERT INTO " . self::USER_TABLE . " (name, username, pin) 
                            VALUES ('" . $name . "', '" . $new_user . "', '" . $new_pin . "');";
        if ($this->picks_db_conn->query($create_user_cmd) === TRUE) {
            return 0;
        } else {
            error_log("Could not create new user\n", 3, $this->log_file);
            return 1;
        }
    }

    /*
    * Returns a users PIN. If the user does not exist, or a pin is not found, 
    * returns -1
    */
    public function get_user_pin(string $user): int
    {
        if (!$this->user_exists($user)) {
            return -1;
        }
        $select_cmd = "SELECT pin FROM " . self::USER_TABLE . " WHERE username='" . $user . "';";
        $result = $this->picks_db_conn->query($select_cmd);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['pin'];
        } else {
            return -1;
        }
    }

    /*
    * Adds pick to Database
    * Returns success code int
    *  0 : Success
    *  1 : Sql Creation error
    *  2 : User does not exist
    */
    public function add_pick(string $user, string $pick, int $week): int
    {
        if (!$this->user_exists($user)) {
            return 2;
        }
        $previous_pick = $this->get_user_pick_for_week($user, $week);
        if (strlen($previous_pick) > 0) {
            $update_cmd = "UPDATE " . self::PICKS_TABLE . " SET pick = '"
                . $pick . "' WHERE username='" . $user . "' AND week_number = " . $week . ";";
            if ($this->picks_db_conn->query($update_cmd) === TRUE) {
                return 0;
            } else {
                return 1;
            }
        } else {
            $add_stmt = $this->picks_db_conn->stmt_init();
            $add_stmt->prepare("INSERT INTO " . self::PICKS_TABLE . " (username, week_number, pick) VALUES (?, ?, ?);");
            $add_stmt->bind_param("sis", $user, $week, $pick);
            $add_stmt->execute();
            return 0;
        }
    }

    /*
    * Deletes all entries from the users list in database
    * Returns success code int
    *  0 : Success
    *  6 : Sql Deletion error
    */
    public function clear_user_table(): int
    {
        $delete_all_users_cmd = "DELETE FROM " . self::USER_TABLE . ";";
        if ($this->picks_db_conn->query($delete_all_users_cmd) === TRUE) {
            return 0;
        } else {
            error_log("Could not clear user list\n", 3, $this->log_file);
            return 6;
        }
    }

    /*
    * Deletes a specific user from the users list in database
    * Returns success code int
    *  0 : Success
    *  2 : User doesn't exist
    *  6 : Sql Deletion error
    */
    public function delete_user(string $user): int
    {
        if (!$this->user_exists($user)) {
            return 2;
        }
        $delete_cmd = "DELETE FROM " . self::USER_TABLE . " WHERE ('username' = '" . $user . "');";
        if ($this->picks_db_conn->query($delete_cmd) === TRUE) {
            return 0;
        } else {
            error_log("Could not delete user\n", 3, $this->log_file);
            return 6;
        }
    }

    /*
    * Deletes all picks from the picks list in database
    * Returns success code int
    *  0 : Success
    *  6 : Sql Deletion error
    */
    public function clear_pick_table(): int
    {
        $delete_all_picks_cmd = "DELETE FROM " . self::PICKS_TABLE . ";";
        if ($this->picks_db_conn->query($delete_all_picks_cmd) === TRUE) {
            return 0;
        } else {
            error_log("Could not clear pick list\n", 3, $this->log_file);
            return 6;
        }
    }


    /*
    * Closes this Access Controller's connections
    */
    public function disconnect()
    {
        $this->picks_db_conn->close();
        $this->sql_conn->close();
    }

    /*
    * Returns boolean indicating if the user exists in the users list
    */
    public function user_exists(string $user): bool
    {
        $find_cmd = "SELECT id, username FROM " . self::USER_TABLE . " WHERE username='" . $user . "';";
        $result = $this->picks_db_conn->query($find_cmd);
        //p]rint_r($result);
        if ($result->num_rows != 0) {
            return true;
        } else {
            return false;
        }
    }


    /*
    * Returns team name of pick for a specific week and user. An empty string
    * indicates that there is no matching pick in the database
    */
    public function get_user_pick_for_week(string $user, int $week): string
    {
        $users_picks = $this->get_user_all_picks($user);
        if (array_key_exists($week, $users_picks)) {
            return $users_picks[$week];
        } else {
            return "";
        }
    }

    /*
    * Returns associative array of users picks. Only populates key value pairs
    * for weeks that a pick exists for the user.
    * key : week number
    * value : team
    */
    public function get_user_all_picks(string $user): array
    {
        $select_cmd = "SELECT pick, week_number FROM " . self::PICKS_TABLE .
            " WHERE username='" . $user . "';";
        $result = $this->picks_db_conn->query($select_cmd);
        $pick_array = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pick_array[$row['week_number']] = $row['pick'];
            }
        }
        return $pick_array;
    }
}
