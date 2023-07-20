<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\{Drive, HtmlHelper, FileHelper};

echo HtmlHelper::showProlog('Просмотр файла');
?>
</head>
<body>
<?php 
$file;
if (isset($_GET['name'])) {
    $file = Drive::viewFile(htmlspecialchars($_GET['name']));
} else {
    $file = Drive::viewFile(htmlspecialchars(''));
}

if (isset($_POST['edit']) && ($file != '')) {
    Drive::editProperties($file);
}
?>
<div class="container-fluid">
    <?=HtmlHelper::showHeader();?>
    <div class="row">
        <div class="col-6">
            <?=HtmlHelper::showMessage('error');?>
            <?php
            if ($file != '') {
                ?>
                    <h3><?=$file['name']?></h3>
                    <form method="POST">
                        <?php
                        /**
                         * Получить как массив всю метаинформацию о файле.
                         */
                        $properties = $file->getProperties();
                        if ($properties) {
                            foreach ($properties as $propertyKey => $propertyValue) {
                                ?>
                                <label for="<?=$propertyKey?>"><?=$propertyKey?></label>
                                <input type="text" name="<?=$propertyKey?>" value="<?=$propertyValue?>">
                                <br>
                                <?php
                            }
                        }
                        ?>
                        <label for="newPropertyKey[0]">Ключ</label>
                        <input type="text" name="newPropertyKey[0]" value="">
                        <label for="newPropertyValue[0]">Значение</label>
                        <input type="text" name="newPropertyValue[0]" value="">
                        <br>
                        <label for="newPropertyKey[1]">Ключ</label>
                        <input type="text" name="newPropertyKey[1]" value="">
                        <label for="newPropertyValue[1]">Значение</label>
                        <input type="text" name="newPropertyValue[1]" value="">
                        <br>
                        <input type="submit" name="edit" class="btn btn-primary">
                    </form>
                <?php
            }
            ?>
        </div>
    </div>
</div>
</body>