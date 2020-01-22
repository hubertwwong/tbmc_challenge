<?php

/*
 * I was using this file to test out db.sql to see if the queries and the tables were correct.
 * Assuming a database of foo exist.
 */

$mysqli = new mysqli("localhost", "root", "password", "foo");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
echo $mysqli->host_info . "\n";

// CREATE TABLES
// ==============
$res = $mysqli->query("
  CREATE TABLE `author` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(20),
    PRIMARY KEY (id)
  ) ENGINE=InnoDB AUTO_INCREMENT=2046711 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
");

$res = $mysqli->query("
CREATE TABLE `comment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `author_id` INT NOT NULL,
  `created_at` TIMESTAMP,
  `comment` VARCHAR(2000),
  INDEX(`created_at`),
  INDEX(`author_id`),
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2046711 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
");

$res = $mysqli->query("
CREATE TABLE `reply` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `author_id` INT NOT NULL,
  `comment_id` INT NOT NULL,
  `reply_id` INT,
  `created_at` TIMESTAMP,
  `comment` VARCHAR(2000),
  INDEX(`created_at`),
  INDEX(`author_id`),
  INDEX(`comment_id`),
  FOREIGN KEY (`author_id`) REFERENCES author(id),
  FOREIGN KEY (`comment_id`) REFERENCES comment(id),
  FOREIGN KEY (`reply_id`) REFERENCES reply(id),  
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2046711 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
");


// Insert some default data.
// ==============
$res = $mysqli->query("
  INSERT INTO author (first_name) VALUES ('John Comment');
");

$res = $mysqli->query("
  INSERT INTO author (first_name) VALUES ('John Reply');
");

$res = $mysqli->query("
  INSERT INTO author (first_name) VALUES ('Jane Reply');
");

$res = $mysqli->query("
  INSERT INTO comment (author_id, comment, created_at) VALUES (2046711, 'root comment','2020-01-20');
");

$res = $mysqli->query("
  INSERT INTO reply (author_id, comment_id, reply_id, created_at, comment) VALUES (2046712, 2046711, null ,'2020-01-20', 'reply to OP');
");

$res = $mysqli->query("
  INSERT INTO reply (author_id, comment_id, reply_id, created_at, comment) VALUES (2046713, 2046711, 2046711,'2020-01-20', 'reply to a reply');
");

$res = $mysqli->query("
  INSERT INTO comment (author_id, comment, created_at) VALUES (2046711, 'root comment2','2020-01-19');
");

$res = $mysqli->query("
  INSERT INTO reply (author_id, comment_id, reply_id, created_at, comment) VALUES (2046713, 2046712, null ,'2020-01-20', 'reply to OP 2');
");

$res = $mysqli->query("
  INSERT INTO comment (author_id, comment, created_at) VALUES (2046714, 'root comment3','2020-01-19');
");
