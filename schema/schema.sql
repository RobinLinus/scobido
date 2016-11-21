DROP TABLE IF EXISTS clicks,votes,trust_matrix,posts,users,users_cache;
DROP PROCEDURE IF EXISTS updateTrust;
DROP FUNCTION IF EXISTS updateTrust;
DROP PROCEDURE IF EXISTS updateUsers;
DROP PROCEDURE IF EXISTS click;
DROP PROCEDURE IF EXISTS vote;
DROP PROCEDURE IF EXISTS post;
DROP FUNCTION IF EXISTS getUserByIp;
# n = number of users
# N = max number of users ~ 200 000 
# m = number of posts 
# M = max number of posts ~ 100 

CREATE TABLE `users` (                                        # Space: O(n)
  `id` bigint(20) unsigned PRIMARY KEY AUTO_INCREMENT,
  `ip` varchar(46) UNIQUE NOT NULL,
  `trust` float NOT NULL DEFAULT 0.5,
  `total_trust` float NOT NULL DEFAULT 0,
  `last_total_users` bigint(20) unsigned NOT NULL DEFAULT 0, 
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_trusted` timestamp NOT NULL,                          # if n > N delete users sorted by oldest last_action
  `last_update` timestamp NOT NULL   
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `trust_matrix` (                                 # Space: O(n * m)
  `from_user` bigint(20) unsigned NULL,
  `to_user` bigint(20) unsigned NULL,
  `trust` float NOT NULL,
  PRIMARY KEY (from_user,to_user),
  FOREIGN KEY (from_user)
        REFERENCES users(id)
        ON DELETE CASCADE,
  FOREIGN KEY (to_user)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `posts` (                                         # Space: O(m)
  `id` bigint(20) unsigned PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `url` varchar(255) NOT NULL UNIQUE,
  `title` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `hash` varchar(255) NOT NULL UNIQUE,

  # calculated fields
  `clicks` int(12) unsigned NOT NULL DEFAULT 0,
  `upvotes` int(12) unsigned NOT NULL DEFAULT 0,
  `downvotes` int(12) unsigned NOT NULL DEFAULT 0,

  `rank` float NOT NULL DEFAULT 0,                           # if m > M delete posts sorted by rank
  `rank_new` float NOT NULL DEFAULT 0,

  `age` float NOT NULL DEFAULT 0,                   
  `rank_raw` float NOT NULL DEFAULT 0,

  UNIQUE KEY `unique_index`(`img`, `title`),
  FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `clicks` (                                         # Space: O(n * m)
  `user_id` bigint(20) unsigned NOT NULL, 
  `post_id` bigint(20) unsigned NOT NULL,

  PRIMARY KEY (post_id,user_id),
  FOREIGN KEY (post_id)
        REFERENCES posts(id)
        ON DELETE CASCADE,
  FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

# O(n * m)
CREATE TABLE `votes` (
  `user_id` bigint(20) unsigned NOT NULL, 
  `post_id` bigint(20) unsigned NOT NULL,
  `vote` BIT,

  PRIMARY KEY (post_id,user_id),
  FOREIGN KEY (post_id)
        REFERENCES posts(id)
        ON DELETE CASCADE,
  FOREIGN KEY (user_id)
        REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `users_cache`(
  `total_users` int NOT NULL DEFAULT 0,
  `trust_sum` float NOT NULL DEFAULT 1
) DEFAULT CHARSET=latin1;

DELIMITER $
CREATE TRIGGER trigger_user_joined BEFORE INSERT ON `users` FOR EACH ROW
  BEGIN
    SET NEW.last_total_users = (SELECT users_cache.total_users+1 FROM users_cache);
    UPDATE users_cache SET 
        total_users = (total_users + 1),
        trust_sum = ( trust_sum + 1 / total_users );
  END;
$

CREATE TRIGGER trigger_user_trusted BEFORE UPDATE ON `users` FOR EACH ROW
  BEGIN
    DECLARE n bigint(20);
    IF (new.trust <> old.trust OR new.total_trust <> old.total_trust )
      THEN 
        SELECT users_cache.total_users INTO n FROM users_cache;
        SET NEW.last_total_users = n;
        UPDATE users_cache
          SET trust_sum = (trust_sum + new.trust / (n * 0.5 + new.total_trust) - old.trust / (old.last_total_users * 0.5 + old.total_trust) );
    END IF;
  END;
$

CREATE TRIGGER trigger_user_deleted BEFORE DELETE ON `users` FOR EACH ROW
  BEGIN
    UPDATE users_cache
      SET 
        trust_sum = (trust_sum - old.trust / (old.last_total_users * 0.5 + old.total_trust)),
        total_users = (total_users - 1);
  END;
$

CREATE FUNCTION updateTrust(user_id bigint(20))
  RETURNS BIT
  BEGIN  
    DECLARE n bigint(20);
    DECLARE s float;
    DECLARE new_trust float;
    SELECT users_cache.total_users,
           users_cache.trust_sum 
            INTO n,s FROM users_cache;
    SELECT (
            IFNULL( 
                sum( trust_matrix.trust * users.trust / ( n * 0.5 + users.total_trust) )
                ,0.0)
                + s * 0.5 )  
            INTO new_trust
            FROM trust_matrix 
            JOIN users
              ON  users.id = trust_matrix.from_user
            WHERE trust_matrix.to_user = user_id;
    UPDATE users
      SET 
        trust = new_trust,
        last_update = now()
      WHERE users.id = user_id;
    RETURN 0;
  END;
$

CREATE TRIGGER trigger_insert_trusted AFTER INSERT ON `trust_matrix` FOR EACH ROW
  BEGIN
    UPDATE users 
      SET
       total_trust = total_trust+new.trust,
       last_trusted = now()
      WHERE id = new.from_user;
  END;
$

CREATE TRIGGER trigger_update_trusted AFTER UPDATE ON `trust_matrix` FOR EACH ROW
  BEGIN
    UPDATE users 
      SET
       total_trust = total_trust+new.trust-old.trust,
       last_trusted = now()
      WHERE id = new.from_user;
  END;
$

CREATE TRIGGER trigger_clicked AFTER INSERT ON `clicks` FOR EACH ROW
  BEGIN
    DECLARE user_id_post bigint(20);
    SELECT posts.user_id INTO user_id_post FROM posts WHERE posts.id = new.post_id;
    IF user_id_post <> new.user_id THEN #interacting with user's own posts doesn't increase trust
      INSERT INTO trust_matrix
        VALUES (new.user_id, user_id_post, 0.125)
        ON DUPLICATE KEY UPDATE trust = (trust + 0.125);
    END IF;
    UPDATE posts 
        SET clicks = clicks + 1
        WHERE posts.id = new.post_id; 
END;
$

CREATE TRIGGER trigger_voted AFTER INSERT ON `votes` FOR EACH ROW
  BEGIN
    DECLARE user_id_post bigint(20);
    SELECT posts.user_id INTO user_id_post FROM posts WHERE posts.id = new.post_id;
    IF user_id_post <> new.user_id THEN #interacting with user's own posts doesn't increase trust
      INSERT INTO trust_matrix
        VALUES (new.user_id, user_id_post, new.vote - 0.5)
      ON DUPLICATE KEY UPDATE trust = (trust + new.vote - 0.5);
    END IF;
    IF new.vote = 1 
      THEN 
        UPDATE posts 
            SET upvotes = upvotes + 1
            WHERE posts.id = new.post_id;
      ELSE 
        UPDATE posts 
            SET downvotes = downvotes + 1
            WHERE posts.id = new.post_id;        
      END IF;
  END;
$



CREATE FUNCTION getUserByIp(ip varchar(46))
  RETURNS bigint(20)  
  BEGIN  
    DECLARE user_id bigint(20);
    SELECT users.id INTO user_id FROM users WHERE users.ip = ip;
    IF user_id IS NULL THEN
       INSERT INTO users (users.ip) VALUES (ip); 
       SET user_id := LAST_INSERT_ID();
    END IF;
    RETURN user_id;
  END;
$

CREATE PROCEDURE updateUsers()
  BEGIN  
    DECLARE fuck_mysql BIT;
    
    IF ( (SELECT total_users FROM users_cache) > 50000 ) 
    THEN
      DELETE FROM users ORDER BY last_trusted ASC LIMIT 5000; 
    END IF;

    CREATE TEMPORARY TABLE tmp_users engine = memory SELECT id FROM users ORDER BY last_update ASC LIMIT 10;  # should be log (n) because every user does at least one action
    SELECT sum(updateTrust(id)) FROM tmp_users INTO fuck_mysql; #need to call updateTrust n log n times
    DROP TEMPORARY TABLE tmp_users;
  END;
$

CREATE PROCEDURE post(ip varchar(46), title varchar(255), url varchar(255), img varchar(255), hash varchar(255))
  BEGIN 
    DECLARE user_id bigint(20);
    DECLARE new_post_id bigint(20);
    
    SELECT getUserByIp(ip) INTO user_id;

    IF ( (SELECT count(*) FROM posts) > 200 ) 
      THEN
        DELETE FROM posts ORDER BY rank ASC LIMIT 50; 
      END IF;

    INSERT INTO posts (title, url, img, hash, user_id) 
      VALUES (title, url, img, hash, user_id);

    SET new_post_id = LAST_INSERT_ID();  

    INSERT INTO clicks (user_id,post_id)            # user clicks his post
      VALUES (user_id,new_post_id);

    INSERT INTO votes (user_id,post_id,vote)        # user likes his post 
      VALUES (user_id,new_post_id,1.0);

    CALL updateUsers();
  END;
$

CREATE PROCEDURE click(ip varchar(46),post_id bigint(20))
  BEGIN  
    INSERT INTO clicks (user_id, post_id) VALUES (getUserByIp(ip),post_id);
    CALL updateUsers();
  END;
$

CREATE PROCEDURE vote(ip varchar(46),post_id bigint(20),vote BIT)
  BEGIN  
    INSERT INTO votes (user_id,post_id,vote) VALUES (getUserByIp(ip),post_id,vote);
    CALL updateUsers();
  END;
$
DELIMITER ;

INSERT INTO users_cache VALUES (0,0.0);


# Testing
-- CALL post('user1','a','a','a','a');
-- CALL post('user2','b','b','b','b');
-- CALL post('user2','c','c','c','c');
-- CALL click('user3', 1);
-- CALL click('user3',2);
-- CALL click('user1',3);
-- CALL click('user4',1);
-- CALL click('user4',2);
-- SELECT SLEEP(1);
-- CALL post('user5','d' ,'d','d','d');
-- CALL post('user5','e' ,'e','e','e');
-- CALL vote('user1',3,1);
-- SELECT SLEEP(1);
-- CALL vote('user3',4,1);
-- CALL click('user3',3);
-- CALL click('user3',4);
-- CALL click('user3',5);
-- SELECT SLEEP(1);
-- CALL post('spammer1','spam1','spam1','spam1','spam1');
-- CALL post('spammer2','spam2','spam2','spam2','spam2');
-- CALL vote('user3',6,0.0);
-- CALL vote('user3',7,0.0);
-- CALL vote('user3',2,1);
-- CALL vote('spammer3',1,0.0);
-- SELECT SLEEP(1);
-- CALL vote('user2',6,0.0);
-- CALL click('user6',3);
-- CALL click('user7',3);
-- CALL click('user8',3);
-- SELECT SLEEP(1);
-- CALL click('user9',3);
-- SELECT SLEEP(1);
-- CALL click('user10',3);
-- SELECT SLEEP(1);
-- CALL click('user11',3);
-- SELECT SLEEP(1);
-- CALL click('user12',3);


# view for posts ranking   

