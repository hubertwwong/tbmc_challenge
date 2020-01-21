<?php
/**
 * Instructions:
 *
 * The following is a poorly written comment handler. Your task will be to refactor
 * the code to improve its structure, performance, and security with respect to the
 * below requirements. Make any changes you feel necessary to improve the code or
 * data structure. The code doesn't need to be runnable we just want to see your
 * structure, style and approach.
 *
 * If you are unfamiliar with PHP, it is acceptable to recreate this functionality
 * in the language of your choice. However, PHP is preferred.
 *
 * Comment Handler Requirements
 * - Users can write a comment
 * - Users can write a reply to a comment
 * - Users can reply to a reply (Maximum of 2 levels in nested comments)
 * - Comments (within in the same level) should be ordered by post date
 * - Address any data security vulnerabilities
 *
 * Data Structure:
 * comments_table
 * -id
 * -parent_id (0 - designates top level comment)
 * -name
 * -comment
 * -create_date
 *
 */
Class CommentHandler {
    private $url;
    private $username;
    private $password;
    private $comment_depth;

    /**
     * Sets up the db connection.
     * 
     * I'm using ENV varaibles to allow to switch the db string values without code changes.
     */
    public function __constrct() {
        // Set the default values
        $this->url = 'testserver';
        $this->username = 'testuser';
        $this->password = 'testpassword';
        $this->comment_depth = 2;
        // Change the comment nesting level if needed.

        // read from env.
        if ($_ENV["DB_URL"]) {
            $this->url = $_ENV["DB_URL"];
        }
        if ($_ENV["DB_USERNAME"]) {
            $this->username = $_ENV["DB_USERNAME"];
        }
        if ($_ENV["DB_PASSWORD"]) {
            $this->password = $_ENV["DB_PASSWORD"];
        }
        if ($_ENV["COMMENT_DEPTH"]) {
            $this->commet_depth = $_ENV["COMMENT_DEPTH"];
        }
    }

    /**
     * Get a DB connection.
     * 
     * From what I'm reading, shouldn't mysqli or PDO be used instead of mysql?
     * Interface seems different so probably would be a breaking change.
     * 
     * My opinion is you probably probably want to mysqli or PDO.
     * You probably want to have a connection pool instead of constructing the connections for each query.
     * I think the db connection object should probably be in a separate file. 
     * 
     * @returns db object or error.
     */
    public function getDB() {
        $db = new mysql($this->url, $this->username, $this->password);
        if (!$db) {
            // Add retry logic or error logic
        }
        return $db;
    }

    /**
     * getComments
     *
     * This function should return a structured array of all comments and replies
     *
     * @return array
     */
    public function getComments() {
        return $this->getCommentsDFS(0, 0);
    }

    /**
     * getCommentsDFS
     * 
     * A DFS traversal to get the comments instead of using loops.
     * You might run into an issue if the comment depth is really deep.
     * At that point you might want to switch to some queue.
     * 
     * @param $id of the parent_id
     * @param $cur_detph is the currernt dfs depth. This is used to stop after hitting n depth.
     * @return array
     */
    public function getCommentsDFS($id, $cur_depth) {
        // if comment level is too deep, just return an empty array.
        if ($cur_depth > $this->comment_depth) {return [];};
        $comments = [];
        $id = 0;
        
        // Runs the query.
        // For any results, try to get replies using the same function using DFS.
        $result = $this->getCommentsWithParent($id);
        while ($row = mysql_fetch_assoc($result)) {
            $replies = $this->getCommentsDFS($row['id'], $cur_depth+1);
            $comment['replies'] = $replies;
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * getCommentsWithParent
     * 
     * A helper function that returns a query with a specific parent id.
     * 
     * @param $parent_id
     * @return MySQL result object.
     */
    public function getCommentsWithParent($parent_id) {
        $db = $this->getDB();
        $sql = "SELECT * FROM comments_table where parent_id=$parent_id ORDER BY create_date DESC;";
        $result = mysql_query($sql, $db);
        return $result;
    }

    /**
     * addComment
     *
     * This function accepts the data directly from the user input of the comment form and creates the comment entry in the database.
     *
     * @param $comment
     * @return string or array
     */
    public function addComment($comment) {
        if (!$this->isValidComment($comment)) {return "save failed. comment not valid.";}
        $db = $this->getDB();
        
        // Flatten out the parent id if needed. Also checks for to see if the parent id is valid in the first place.
        $comment['parent_id'] = $this->getValidCommentParentID($comment['parent_id']);

        // You want to check for a valid parent id before saving.
        if ($comment['parent_id'] != null) {
            // Not great since you can still have an sql inejction.
            // I think you are suppose to use the mysqli prepared statement or PDO extension.
            $sql = "INSERT INTO comments_table (parent_id, name, comment, create_date) VALUES (" . $this->sanatize($comment['parent_id']) . ", " . $this->sanatize($comment['name']) . ", " . $this->sanatize($comment['comment']) . ", NOW())";
            $result = mysql_query($sql, $db);
            if($result) {
                $id = mysql_insert_id();
                $sql = "SELECT * FROM comments_table where id=" . $id . ";";
                $result = mysql_query($sql, $db);
                $comment = mysql_result($result, 0);
                return $comment;
            } else {
                return 'save failed';
            }
        }
    }
    // sql inejction.
    // also assuming the value are valid.
    // are you assuming that the parent comment exist too?
    // 2 levels of enforcement.

    /**
     * getValidCommentParentID
     * 
     * This function basically gets a valid parent ID if possible.
     * Enforces the business logic of comments being 2 levels deep.
     * If it tries to nest more than 2 levels deep, return the 2nd comment id.
     * 
     * @param $parent_id of a comment
     * @return integer or null
     */
    public function getValidCommentParentID($parent_id) {
        $db = $this->getDB();
        $cur_id = $parent_id;
        $sql = "SELECT * FROM comments_table where id=$cur_id";
        $parents = array(); // store the list of parents_ids
        
        // Follow the chain of parent comments to the top.
        $result = mysql_query($sql, $db);
        if ($result) {
            // check if the results returns anything.
            while($result) {
                // Checking if rows were returned for a given id.
                if ($result->num_rows == 0) {return null;}
                array_push($parents, $cur_id);

                // find the parent id of that comment.
                $cur_id = $result['parent_id'];

                // You want to check if its a top level comment.
                // I'm not sure if that comment id is stored in the db.
                if ($cur_id == 0) {
                    array_push($parents, 0);
                    break;
                }

                // run the query again.
                $result = mysql_query($sql, $db);
            }
        } else {
            // A valid parent id was not found on the initial check, just bail.
            return null;
        }

        // Determine which parent id to return.
        // If you have more parents than the depth specified, return the value specified by the business logic.
        $depth = count($parents) - $this->$comment_level - 1;
        if ($depth > 0) { 
            $parents[$depth]; 
        }
        return $parent_id;
    }

    /**
     * isVaidComment
     * 
     * This helper function checks if a comment is a valid comment.
     * Encapsulate validation logic. You might have other routes that inserts data.
     * 
     * @return boolean
     */
    public function isValidComment($comment) {
        // Not sure how php checks its falsy but want to have a basic check if something was actually passed in.
        if (!$comment) {return false;}

        $valid = true;
        // A simple check just to see if the fields are there.
        // Should have checks on the fields themselves. At a minimum, some regex
        // I just put a simple length check.
        // Probably should have a check of the size of the fields. You don't want to have the end user to dump gigs of info into the db.
        if (!$comment['name'] && !$comment['name'] && strlen($comment['name']) < 255) {$valid=false;}
        if ($valid==true && !$comment['parent_id'] && strlen($comment['parent_id']) < 64) {$valid=false;}
        if ($valid==true && !$comment['comment'] && strlen($comment['comment']) < 6000) {$valid=false;}

        return $valid;
    }

    /**
     * sanatize
     * 
     * Encapsulate santization logic.
     * For now, just add mysql escape.
     * Will be able to expand as needed.
     * 
     * @return string
     */
    public function sanatize($var) {
        return mysql_real_escape_string($var);
    }
}
