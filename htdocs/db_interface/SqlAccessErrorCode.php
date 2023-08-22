<?php

namespace SqlAccess;

$SqlAccessErrorCodeOk = 0;
$SqlAccessErrorCodeERR_CREATE_ERROR = 1;
$SqlAccessErrorCodeERR_USER_INVALID = 2;
$SqlAccessErrorCodeERR_USER_EXISTS = 3;
$SqlAccessErrorCodeERR_PICK_INVALID = 4;
$SqlAccessErrorCodeERR_INSERT_ERROR = 5;
$SqlAccessErrorCodeERR_DELETE_ERROR = 6;

/** Not supported before PHP 8
enum SqlAccessErrorCode: int
{
    case OK = 0;
    case ERR_CREATE_ERROR = 1;
    case ERR_USER_INVALID = 2;
    case ERR_USER_EXISTS  = 3;
    case ERR_PICK_INVALID = 4;
    case ERR_INSERT_ERROR = 5;
}
 */
/*
 * For reference, the columns of the two tables
 *
 * Table: Users
 * Column 1: id (int, autoincrementing)
 * Column 2: username (max 30 characters)
 *
 * Table: Picks
 * Column 1: pickid (int, autoincrementing)
 * Column 2: username (max 30 characters)
 * Column 3: week_number (int)
 * Column 4: pick (max 60 characters)
 */
