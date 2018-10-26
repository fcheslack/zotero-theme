<?php if (!defined('APPLICATION')) exit(); ?>
<?php

$desc = t('To prevent abuse, some tools automatically hide content and list it here until it is manually approved by a moderator.');
helpAsset($this->data('Title'), $desc);

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
echo heading($this->data('Title'));
?>
<div class="toolbar flex-wrap">
    <div class="toolbar-buttons">
        <?php
        echo anchor(t('Approve'), '#', ['class' => 'RestoreButton btn btn-primary']);
        echo anchor(t('Delete Forever'), '#', ['class' => 'DeleteButton btn btn-primary']);
        echo anchor(t('Spammer'), '#', ['class' => 'SpammerButton btn btn-primary']);
        ?>
    </div>
    <div class="search toolbar-main"><?php
        if (c('Vanilla.Categories.Use')) {
            echo '<div class="input-wrap input-wrap-multiple">';
            echo $this->Form->categoryDropDown('CategoryID', [
                    'Value' => val('ModerationCategoryID', $this->Data),
                    'IncludeNull' => t('Show all categories')]
            );
            echo anchor(t('Filter'), '#', ['class' => 'FilterButton btn btn-primary']);
            echo '</div>';
        }
        ?></div>
    <?php PagerModule::write(['Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard']); ?>
</div>
<?php
echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div>';
?>
<?php
$this->addDefinition('ExpandText', t('more'));
$this->addDefinition('CollapseText', t('less'));
echo $this->Form->close();
?>
<?php /*
<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
    <div
        class="Info"><?php echo t('To prevent abuse, some tools automatically hide content and list it here until it is manually approved by a moderator.'); ?></div>

<?php
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="FilterMenu"><?php
        if (c('Vanilla.Categories.Use')) {
            echo wrap(sprintf(
                    t('Vanilla.Moderation.FilterBy', 'Show moderation queue for %1$s'),
                    $this->Form->CategoryDropDown('CategoryID', array(
                        'Value' => val('ModerationCategoryID', $this->Data),
                        'IncludeNull' => 'Everything'))
                ).' '.anchor(t('Filter'), '#', array('class' => 'FilterButton SmallButton')), 'div');
        }
        ?></div>
    <div class="Info">
        <?php
        echo anchor(t('Approve'), '#', array('class' => 'RestoreButton SmallButton'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
        echo anchor(t('Spammer'), '#', array('class' => 'SpammerButton SmallButton'));
        ?>
    </div>
<?php
echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
    <div class="Info">
        <?php
        echo anchor(t('Approve'), '#', array('class' => 'RestoreButton SmallButton'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
        echo anchor(t('Spammer'), '#', array('class' => 'SpammerButton SmallButton'));
        ?>
    </div>
<?php

$this->addDefinition('ExpandText', t('(more)'));
$this->addDefinition('CollapseText', t('(less)'));
echo $this->Form->close();
*/?>
