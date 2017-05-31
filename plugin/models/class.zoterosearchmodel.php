<?php
/**
 * Zotero Search model.
 */

/**
 * Overrides SearchModel to allow for shorter full text queries with a non-default mysql ft_min_word_len value
 */
class ZoteroSearchModel extends SearchModel {
    /**
     *
     *
     * @param $Search
     * @param int $Offset
     * @param int $Limit
     * @return array|null
     * @throws Exception
     */
    public function search($Search, $Offset = 0, $Limit = 20) {
        // If there are no searches then return an empty array.
        if (trim($Search) == '') {
            return array();
        }
        //add + before each term
        $split = explode(' ', $Search);
        foreach($split as $i => $val){
            if($val[0] != '+' && $val[0] != '-'){
                $split[$i] = '+' . $val;
            }
        }
        $Search = implode(' ', $split);

        // Figure out the exact search mode.
        if ($this->ForceSearchMode) {
            $SearchMode = $this->ForceSearchMode;
        } else {
            $SearchMode = strtolower(c('Garden.Search.Mode', 'matchboolean'));
        }
        
        if ($SearchMode == 'matchboolean') {
            if (strpos($Search, '+') !== false || strpos($Search, '-') !== false) {
                $SearchMode = 'boolean';
            } else {
                $SearchMode = 'match';
            }
        } else {
            $this->_SearchMode = $SearchMode;
        }

        if ($ForceDatabaseEngine = c('Database.ForceStorageEngine')) {
            if (strcasecmp($ForceDatabaseEngine, 'myisam') != 0) {
                $SearchMode = 'like';
            }
        }
        
        /*
        if (strlen($Search) <= 4) {
            $SearchMode = 'like';
        }
        */

        $this->_SearchMode = $SearchMode;

        $this->EventArguments['Search'] = $Search;
        $this->fireEvent('Search');

        if (count($this->_SearchSql) == 0) {
            return array();
        }
        
        // Perform the search by unioning all of the sql together.
        $Sql = $this->SQL
            ->select()
            ->from('_TBL_ s')
            ->orderBy('s.DateInserted', 'desc')
            ->limit($Limit, $Offset)
            ->GetSelect();

        $Sql = str_replace($this->Database->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $this->_SearchSql)."\n)", $Sql);

        $this->fireEvent('AfterBuildSearchQuery');

        if ($this->_SearchMode == 'like') {
            $Search = '%'.$Search.'%';
        }

        foreach ($this->_Parameters as $Key => $Value) {
            $this->_Parameters[$Key] = $Search;
        }

        $Parameters = $this->_Parameters;
        $this->reset();
        $this->SQL->reset();
        
        $Result = $this->Database->query($Sql, $Parameters)->resultArray();
        
        foreach ($Result as $Key => $Value) {
            if (isset($Value['Summary'])) {
                $Value['Summary'] = Condense(Gdn_Format::to($Value['Summary'], $Value['Format']));
                $Result[$Key] = $Value;
            }

            switch ($Value['RecordType']) {
                case 'Discussion':
                    $Discussion = arrayTranslate($Value, array('PrimaryID' => 'DiscussionID', 'Title' => 'Name', 'CategoryID'));
                    $Result[$Key]['Url'] = DiscussionUrl($Discussion, 1);
                    break;
                
                case 'Comment':
                    $Comment = arrayTranslate($Value, array('PrimaryID' => 'CommentID', 'Title' => 'Name', 'CategoryID'));
                    $Result[$Key]['Url'] = CommentUrl($Comment);
                    break;
            }
        }

        return $Result;
    }

    public function feedSearch($Limit=50) {
        $Sql = "(select '0' as `Relavence`, d.DiscussionID as `PrimaryID`, d.Name as `Title`, d.Body as `Summary`, d.Format as `Format`, d.CategoryID as `CategoryID`, d.Score as `Score`, concat('/discussion/', d.DiscussionID) as `Url`, d.DateInserted as `DateInserted`, d.InsertUserID as `UserID`, 'Discussion' as `RecordType` from GDN_Discussion d ORDER BY DateInserted DESC LIMIT $Limit) UNION (select '0' as `Relavence`, c.CommentID as `PrimaryID`, d.Name as `Title`, c.Body as `Summary`, c.Format as `Format`, d.CategoryID as `CategoryID`, c.Score as `Score`, concat('/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID) as `Url`, c.DateInserted as `DateInserted`, c.InsertUserID as `UserID`, 'Comment' as `RecordType` from GDN_Comment c left join GDN_Discussion d on d.DiscussionID = c.DiscussionID ORDER BY DateInserted DESC LIMIT $Limit) ORDER BY DateInserted DESC LIMIT $Limit";
        $Parameters = [];
        
        $Result = $this->Database->query($Sql, $Parameters)->resultArray();
        
        foreach ($Result as $Key => $Value) {
            switch ($Value['RecordType']) {
                case 'Discussion':
                    $Discussion = arrayTranslate($Value, array('PrimaryID' => 'DiscussionID', 'Title' => 'Name', 'CategoryID'));
                    $Result[$Key]['Url'] = DiscussionUrl($Discussion);
                    break;
                
                case 'Comment':
                    $Comment = arrayTranslate($Value, array('PrimaryID' => 'CommentID', 'Title' => 'Name', 'CategoryID'));
                    $Result[$Key]['Url'] = CommentUrl($Comment);
                    break;
            }
        }

        return $Result;
    }
}
