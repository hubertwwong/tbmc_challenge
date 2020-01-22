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
    private $db_name;

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
        $this->db_name = 'testdb';
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
        if ($_ENV["DB_NAME"]) {
            $this->db_name = $_ENV["DB_NAME"];
        }
    }

    /**
     * Get a DB connection.
     * 
     * Using mysqli instead of mysql
     * One thing i'm not sure if the db_name is required. I didn't see one on the starter code but when I was reading the docs. I notice a db name being a param.
     * 
     * @return db object or null.
     */
    public function getDB() {
        $db = new mysqli($this->url, $this->username, $this->password, $this->db_name);
        if ($db->connect_errno) {
            return null;
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
        // Intial arguments are 0 for the root comments.
        // 0 for the depth.
        return $this->getCommentsDFS(0, 1);
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
        while ($row = mysqli_fetch_assoc($result)) {
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
        $result = mysqli_query($sql, $db);
        $db->close();
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
        if (!$this->isValidComment($comment)) {return "Save failed. Comment did not pass validation.";}
        $db = $this->getDB();
        
        // Flatten out the parent id if needed. Also checks for to see if the parent id is valid in the first place.
        $comment['parent_id'] = $this->getValidCommentParentID($comment['parent_id']);

        // You want to check for a valid parent id before saving.
        if ($comment['parent_id'] != null) {
            try {
                // Insert the comment.
                // Use a prepared statement to avoid the sql injection.
                $stmt = $db->prepare("INSERT INTO comments_table (parent_id, name, comment, create_date) VALUES (?, ?, ?, ?)");
                if (!$stmt) {return "Prepare statement failed";};
                $stmt->bind_param("isss", $comment['parent_id'], $comment['name'], $comment['comment'], NOW());
                if (!$stmt) {return "Bind statement failed";};
                // Unsure of the date being a string in the case of prepared statements.
                $stmt->execute();
                // get the primary key of the inserted value
                // checking if the id was positive. A basic check.
                if ($db->insert_id >= 0) {
                    // Return the comment row of what you inserted.
                    $id = $db->insert_id;
                    $sql = "SELECT * FROM comments_table where id=" . $id . ";";
                    $result = mysqli_query($sql, $db);
                    $comment = mysqli_result($result, 0);
                    $db->close();
                    return $comment;
                } else {
                    $db->close();
                    return 'Prepred statemet failed to execute.';
                }
            } catch(Exception $e) {
                $db->close();
                return 'save failed';
            }
        } else {
            $db->close();
            return 'Parent ID not found.';
        }
    }

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
        $result = mysqli_query($sql, $db);
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
                $result = mysqli_query($sql, $db);
            }
        } else {
            // A valid parent id was not found on the initial check, just bail.
            $db->close();
            return null;
        }

        // Determine which parent id to return.
        // If you have more parents than the depth specified, return the value specified by the business logic.
        $depth = count($parents) - $this->$comment_level - 1;
        if ($depth > 0) { 
            $parent_id = $parents[$depth]; 
        }

        $db->close();
        return $parent_id;
    }

    /**
     * isVaidComment
     * 
     * This helper function checks if a comment is a valid comment.
     * Encapsulate validation logic. You might have other routes that inserts data.
     * 
     * @param $comment object
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
        if ($valid==true && !$comment['comment'] && strlen($comment['comment']) < 2000) {$valid=false;}

        return $valid;
    }
}
