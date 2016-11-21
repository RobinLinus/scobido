<?php include "head-tpl.php";?>
	<header>
		<a href="/hot" class="active"><img src="/img/icons/hot.svg"> Hot</a><a href="/new"><img src="/img/icons/new.svg"> New</a>
	</header>	
	<div class="scroller animated fadeIn">
		<div class="content">
			<?php
				include_once "db.php";
				include_once "post-tpl.php";
				global $mysqli;

				$query = "SELECT id,title,url,img,created_at,clicks,rank,upvotes,downvotes 
							FROM posts 
							ORDER BY rank DESC 
							LIMIT 18";
				$stmt = $mysqli->prepare($query);
				$stmt->execute();
				$stmt->bind_result($id,$title,$url,$img,$time,$clicks,$rank,$upvotes,$downvotes);
			
				while($stmt->fetch()){ 
		    		postTemplate($id,$url,$title,$img,$time,$clicks,$upvotes,$downvotes);
				}
				$stmt->close();
			?>
		</div>
	</div>
	<footer>Frontpage of the World Wide Web.</footer>
	<script src="/vote.js" async defer></script>
</body>
</html>

