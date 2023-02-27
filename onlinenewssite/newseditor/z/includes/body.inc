<body>
  <header>
    <nav>
      <form method="post" action="<?php echo $uri; ?>logout.php">
        <span class="fr"><input type="submit" class="button" name="login" value="Log out <?php echo html($_SESSION['username']); ?>"></span>
      </form>

      <div class="logo">
        <a href="<?php echo $uri; ?>"><img src="images/logo.png" class="logo" alt="Online News Site Software"></a>
      </div>
    </nav>
  </header>
