<!doctype html >
<?php
require "./core/test.inc.php";
?>
<html>
  <head>
    <title>Test Project</title>
    <link href="<?php echo $CFG->stylesheet ?>" rel="stylesheet">
    <?php
    echo $CFG->doMeta();
    ?>
  </head>
  <body>
    <h1>Test Project</h1>
    <p><?php echo show_body($_GET['p']) ?></p>
  </body>
</html>