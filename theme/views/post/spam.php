<?php if (!defined('APPLICATION')) exit();
error_log("zotero theme spam.php");
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->close();

$Message = '<p>Posts from new users are moderated. Your message will appear after it has been approved.</p>';
if ($this->data('DiscussionUrl')) {
    $Message .= '<div>' . sprintf(t('Click <a href="%s">here</a> to go back to the discussion.') . url($this->data('DiscussionUrl'))) . '</div>';
} else {
    $Message .= '<div>' . anchor('Back to the discussions list.', 'discussions') . '</div>';
}

$this->InformMessage($Message, 'ModerationPending');
?>
<div class="Info">
    <?php
    if ($this->RequestMethod == 'discussion')
        $Message = t('DiscussionRequiresApproval', "Your discussion will appear after it is approved.");
    else
        $Message = t('CommentRequiresApproval', "Your comment will appear after it is approved.");
    echo '<div>', $Message, '</div>';

    if ($this->data('DiscussionUrl'))
        echo '<div>', sprintf(t('Click <a href="%s">here</a> to go back to the discussion.'), url($this->data('DiscussionUrl'))), '</div>';
    else
        echo '<div>', anchor('Back to the discussions list.', 'discussions'), '</div>';
    ?>
</div>
