<?php if (!defined('APPLICATION')) exit(); ?>
<div class="DataBox DataBox-EndorsedComments"><span id="endorsed"></span>
   <h2 class="CommentHeading">Endorsed Comments</h2>
   <ul class="MessageList DataList EndorsedComments">
        <?php
        $Sender->EventArguments['EndorsedPullup'] = true;
        foreach($Sender->Data('Endorsed') as $Row) {
            $Sender->EventArguments['Comment'] = $Row;
            WriteComment($Row, $Sender, Gdn::Session(), 0);
        }
        $Sender->EventArguments['EndorsedPullup'] = false;
        ?>
   </ul>
</div>
