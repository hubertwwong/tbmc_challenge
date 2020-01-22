/*
Application requires threaded commenting system. 
There will be an author and comments belong to the author.

Please write create statements for tables accordingly and write query to be run on application that will return :
- All the comments sorted by created date
- Replies to those comments
- first_name of the author for each comment
- Created date of every comment

Keep in mind the best performance.
You can add/edit columns to the tables or create additional tables if necessary.
Consider adding foreign key constraints, indices etc.
*/

/* AUTHOR TABLE */
CREATE TABLE `author` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(20),
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2046711 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

/* COMMENT TABLE */
CREATE TABLE `comment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `author_id` INT NOT NULL,
  `created_at` TIMESTAMP,
  `comment` VARCHAR(2000),
  INDEX(`created_at`),
  INDEX(`author_id`),
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2046711 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

/* REPLY TABLE */
CREATE TABLE `reply` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `author_id` INT NOT NULL,
  `comment_id` INT NOT NULL,
  `reply_id` INT,
  `created_at` TIMESTAMP,
  `comment` VARCHAR(2000),
  INDEX(`created_at`),
  INDEX(`comment_id`),
  FOREIGN KEY (`author_id`) REFERENCES author(id),
  FOREIGN KEY (`comment_id`) REFERENCES comment(id),
  FOREIGN KEY (`reply_id`) REFERENCES reply(id),  
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2046711 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

/* INSERT SOME DATA TO TEST */
INSERT INTO author (first_name) VALUES ('John Comment');
INSERT INTO author (first_name) VALUES ('John Reply');
INSERT INTO author (first_name) VALUES ('Jane Reply');
INSERT INTO author (first_name) VALUES ('Jane Comment');


INSERT INTO comment (author_id, comment, created_at) VALUES (2046711, 'root comment','2020-01-20');
INSERT INTO reply (author_id, comment_id, reply_id, created_at, comment) VALUES (2046712, 2046711, null ,'2020-01-20', 'reply to OP');
INSERT INTO reply (author_id, comment_id, reply_id, created_at, comment) VALUES (2046713, 2046711, 2046711,'2020-01-20', 'reply to a reply');

INSERT INTO comment (author_id, comment, created_at) VALUES (2046711, 'root comment2','2020-01-19');
INSERT INTO reply (author_id, comment_id, reply_id, created_at, comment) VALUES (2046713, 2046712, null ,'2020-01-20', 'reply to OP 2');

INSERT INTO comment (author_id, comment, created_at) VALUES (2046714, 'root comment3','2020-01-19');


/* QUERY */

/* All the comments sorted by created date  */
SELECT comment FROM comment ORDER BY created_at;

/* Replies to those comments */
SELECT comment FROM reply ORDER BY comment_id;

/* first_name of the author for each comment */
SELECT comment, first_name FROM comment INNER JOIN author ON comment.author_id=author.id;

/* Created date of every comment */
SELECT created_at FROM comment;