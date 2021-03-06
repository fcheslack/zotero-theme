<?php if (!defined('APPLICATION')) exit(); ?>
    <description><?php /*echo Gdn_Format::text($this->Head->title());*/ ?>Recent Comments</description>
    <language><?php echo Gdn::config('Garden.Locale', 'en-US'); ?></language>
    <atom:link href="<?php echo htmlspecialchars(url($this->SelfUrl, true)); ?>" rel="self" type="application/rss+xml"/>
<?php

foreach ($this->Data['SearchResults'] as $Comment) {
    ?>
    <item>
        <title><?php echo Gdn_Format::text($Comment['Title']); ?></title>
        <link><?php echo $Comment['Url']; ?></link>
        <pubDate><?php echo date('r', Gdn_Format::ToTimeStamp($Comment['DateInserted'])); ?></pubDate>
        <category><?php echo Gdn_Format::text(CategoryModel::categories($Comment['CategoryID'])['Name']); ?></category>
        <dc:creator><?php echo Gdn_Format::text($Comment['Name']); ?></dc:creator>
        <guid isPermaLink="false"><?php echo $Comment['Url']; ?></guid>
        <description><![CDATA[<?php echo str_replace("\r", "", Gdn_Format::RssHtml($Comment['Summary'], $Comment['Format'])); ?>]]></description>
    </item>
<?php
}
