<?php

class TutorPHP
{

    const sGathererBaseURL = 'http://gatherer.wizards.com/Pages/';
    const sGathererImageURL = 'http://gatherer.wizards.com/Handlers/Image.ashx?type=card&multiverseid=';
    const sDivPrefix = 'ctl00_ctl00_ctl00_MainContent_SubContent_SubContent';
    const sPaginationControl = 'ctl00_ctl00_ctl00_MainContent_SubContent_topPagingControlsContainer';

    protected $aSymbols = array(
        '{White}' => 'W',
        '{White or Blue}' => 'WU',
        '{White or Green}' => 'WG',
        '{White or Black}' => 'WB',
        '{White or Red}' => 'WR',
        '{Phyrexian White}' => 'W/P',
        '{Blue}' => 'U',
        '{Blue or Black}' => 'UB',
        '{Blue or Green}' => 'UB',
        '{Blue or Red}' => 'UB',
        '{Phyrexian Blue}' => 'U/P',
        '{Black}' => 'B',
        '{Black or Green}' => 'BG',
        '{Black or Red}' => 'BR',
        '{Phyrexian Black}' => 'B/P',
        '{Red}' => 'R',
        '{Red or Green}' => 'RG',
        '{Phyrexian Red}' => 'R/P',
        '{Green}' => 'G',
        '{Green or White}' => 'GW',
        '{Phyrexian Green}' => 'G/P',
        '{Two}' => '2',
        '{Variable Colorless}' => 'X',
        '{Snow}' => 'S',
        '{Tap}' => 'T',
        '{Untap}' => 'Q');
    protected $aSupertypes = array(
        'Basic',
        'Legendary',
        'Ongoing',
        'Snow',
        'World'
    );
    protected $sPrefix = '';


    public function __construct()
    {
        
    }


    /**
     * Fetches an array of card information based on the card ID
     *
     * @param type $iCardID The Card ID we require information on
     *
     * @throws Exception
     */
    public function fetchCard($iCardID)
    {

        if (strlen($iCardID) === 0 || !is_int($iCardID)) {
            throw new Exception('Invalid Card ID');
        }

        $bDoubleSided = false;
        $this->sPrefix = '';
        $aCardDetails = array();
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $oHTML = new DOMDocument();
        $oHTML->loadHTML($this->fetchPage(self::sGathererBaseURL . 'Card/Details.aspx?multiverseid=' . (int) $iCardID));
        $oXPath = new DOMXPath($oHTML);

        if ($this->isDoubleSided($oXPath)) {
            $bDoubleSided = true;
            $this->sPrefix = '_ctl05';
        }

        //Extract Name
        $aCardDetails['name'] = $this->extractName($oXPath);

        //Extract Mana Cost
        $aCardDetails['mana_cost'] = $this->extractManaCost($oXPath);

        //Extract Converted Mana Cost
        $aCardDetails['converted_mana_cost'] = $this->extractedConvertedManaCost($oXPath);

        //Extract Types string
        $sTypes = $this->extractTypes($oXPath);

        $aCardDetails['supertypes'] = $this->calculateSuperType($sTypes);
        $aCardDetails['types'] = $this->calculateTypes($sTypes);
        $aCardDetails['subtypes'] = $this->calculateSubTypes($sTypes);

        //Power Toughness/Legalites
        if ($this->isPlaneswalker($aCardDetails['types'])) {
            //Loyalty
            $aCardDetails['loyalty'] = $this->extractLoyalty($oXPath);
        } else {
            //Extract Power/Toughness
            $aPowerToughness = $this->extractPowerToughness($oXPath);

            if (isset($aPowerToughness[0])) {
                $aCardDetails['power'] = $aPowerToughness[0];
            }

            if (isset($aPowerToughness[1])) {
                $aCardDetails['toughness'] = $aPowerToughness[1];
            }
        }

        //Versions
        $aCardDetails['versions'] = $this->extractVersions($oXPath);

        //community_rating
        $aCardDetails['community_rating']['rating'] = $this->extractCommunityRating($oXPath);

        //Total Votes
        $aCardDetails['community_rating']['votes'] = $this->extractTotalVotes($oXPath);

        //Extract Ability Text
        $aCardDetails['text'] = $this->extractAbilityText($oXPath);

        //Flavor
        $aFlavorText = $this->extractFlavorText($oXPath);
        $aCardDetails['flavor_text'] = @iconv("UTF-8", "ASCII//TRANSLIT", $aFlavorText[0]);

        //Flavor Text Attribution
        if (isset($aFlavorText[1])) {
            $aCardDetails['flavor_text_attribution'] = @iconv("UTF-8", "ASCII//TRANSLIT", $aFlavorText[1]);
        }

        //Color Indicator
        $aCardDetails['color_indicator'] = $this->extractColorIndicator($oXPath);

        //Watermark
        $aCardDetails['watermark'] = $this->extractWatermark($oXPath);

        //Extract Set
        $aCardDetails['expansion'] = $this->extractExpansion($oXPath);

        //Rarity
        $aCardDetails['rarity'] = $this->extractRarity($oXPath);

        //Number
        $aCardDetails['number'] = $this->extractExpansionNumber($oXPath);

        //Artist
        $aCardDetails['artist'] = $this->extractArtist($oXPath);

        //rulings
        $aCardDetails['rulings'] = $this->extractRulings($oXPath);

        //CardID Link
        $aCardDetails['card_id'] = $this->extractCardID($oXPath);

        //gatherer_url
        $aCardDetails['gatherer_url'] = self::sGathererBaseURL . 'Card/Details.aspx?multiverseid=' . $iCardID;

        //image_url
        $aCardDetails['image_url'] = self::sGathererImageURL . $iCardID;

        //Extract Alternate Side (Werewolves, same page)
        if ($bDoubleSided) {
            $aCardDetails['alternate_side'] = $this->extractAlternateSide($oXPath);
        } elseif (($sUrl = $this->isDoubleFaced($oXPath)) !== false) {
            //Extract dual side face (Life/Death), different page
            $aCardDetails['alternate_face'] = $this->extractDualSide($sUrl);
        }

        libxml_use_internal_errors(false);

        foreach ($aCardDetails AS $k => $v) {
            if (!is_array($v) && strlen($v) === 0) {
                unset($aCardDetails[$k]);
            }
        }

        return $aCardDetails;

    }


    /**
     * Extracts an array of card legalities for the provided card id
     *
     * @param int $iCardID	The Card ID we want legalities for
     *
     * @return type
     * @throws Exception
     */
    public function fetchCardLegalities($iCardID)
    {

        if (strlen($iCardID) === 0 || !is_int($iCardID)) {
            throw new Exception('Invalid Card ID');
        }

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $oHTML = new DOMDocument();
        $oHTML->loadHTML($this->fetchPage(self::sGathererBaseURL . 'Card/Printings.aspx?multiverseid=' . (int) $iCardID));
        $oXPath = new DOMXPath($oHTML);

        //Extract Rules
        $aLegalityRows = $oXPath->query('//p[@class="text" and contains(., "This card has restrictions in the following formats:")]/following-sibling::table/tr');
        $iRow = 0;
        $aHeaders = array();
        $aLegalties = array();
        foreach ($aLegalityRows as $sTmp) {
            $aLegalityColumn = $oXPath->query('./td', $sTmp);
            $x = 0;
            foreach ($aLegalityColumn AS $sColumnRow) {
                if ($iRow === 0) {
                    $aHeaders[$x] = trim($sColumnRow->nodeValue);
                } else {
                    $aLegalties[$iRow][$aHeaders[$x]] = trim($sColumnRow->nodeValue);
                }
                $x++;
            }
            $iRow++;
        }

        libxml_use_internal_errors(false);
        return $aLegalties;

    }


    /**
     * Extract a list of set IDs, using the set name
     *
     * @param string $sSetName The set we're interested in
     *
     * @return array
     * @throws Exception
     */
    public function fetchSetCardIDs($sSetName)
    {
        if (strlen($sSetName) === 0) {
            throw new Exception('Invalid Set Name.');
        }

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $oHTML = new DOMDocument();
        $oHTML->loadHTML($this->fetchPage(self::sGathererBaseURL . 'Search/Default.aspx?set=[%22' . rawurlencode($sSetName) . '%22]'));
        $oXPath = new DOMXPath($oHTML);

        //Extracts the pagination
        $aCardPages = $oXPath->query('//div[@id="' . self::sPaginationControl . '"]/a/@href');
        $iTotalPages = 0;
        foreach ($aCardPages As $aCardPage) {
            $iTotalPages++;
        }

        if ($iTotalPages === 0) {
            throw new Exception('Unable to find the set and/or any pagination.');
        }

        $aCardIDs = array();
        for ($x = 0; $x <= $iTotalPages; $x++) {
            $oHTML = new DOMDocument();
            $oHTML->loadHTML($this->fetchPage(self::sGathererBaseURL . 'Search/Default.aspx?set=[%22' . rawurlencode($sSetName) . '%22]&page=' . $x));

            $oXPath = new DOMXPath($oHTML);
            $aCardUrls = $oXPath->query('//span[@class="cardTitle"]/a/@href');

            foreach ($aCardUrls AS $aCard) {
                $aCardIDs[] = (int) str_replace('../Card/Details.aspx?multiverseid=', '', $aCard->nodeValue);
            }
        }

        sort($aCardIDs);
        return array_unique($aCardIDs);

    }


    /**
     * Extract the alternate side of the card (On the same html page)
     *
     * @param object $oXPath The xPath object
     *
     * @return array
     */
    public function extractAlternateSide($oXPath)
    {

        $this->sPrefix = '_ctl06';
        $aCardDetails['name'] = $this->extractName($oXPath);

        //Extract Types string
        $sTypes = $this->extractTypes($oXPath);

        //Types
        $aCardDetails['supertypes'] = $this->calculateSuperType($sTypes);
        $aCardDetails['types'] = $this->calculateTypes($sTypes);
        $aCardDetails['subtypes'] = $this->calculateSubTypes($sTypes);

        //Ability Text
        $aCardDetails['text'] = $this->extractAbilityText($oXPath);

        //Color Indicator
        $aCardDetails['color_indicator'] = $this->extractColorIndicator($oXPath);

        //Power/Toughness/Loyalty
        $aPowerToughness = $this->extractPowerToughness($oXPath);
        if (isset($aPowerToughness[0])) {
            $aCardDetails['power'] = $aPowerToughness[0];
        }

        if (isset($aPowerToughness[1])) {
            $aCardDetails['toughness'] = $aPowerToughness[1];
        }

        //Extract Set
        $aCardDetails['expansion'] = $this->extractExpansion($oXPath);

        //Rarity
        $aCardDetails['rarity'] = $this->extractRarity($oXPath);

        //Number
        $aCardDetails['number'] = $this->extractExpansionNumber($oXPath);

        //Artist
        $aCardDetails['artist'] = $this->extractArtist($oXPath);

        //community_rating
        $aCardDetails['community_rating']['rating'] = $this->extractCommunityRating($oXPath);

        //Total Votes
        $aCardDetails['community_rating']['votes'] = $this->extractTotalVotes($oXPath);

        //CardID Link
        $aCardDetails['card_id'] = $this->extractCardID($oXPath);

        $this->sPrefix = '';
        return $aCardDetails;

    }


    /**
     * Extract the second face of a card (Different HTML Page, think Life/Death)
     *
     * @param string $sUrl The URL of the dualside of the card
     *
     * @return array
     */
    public function extractDualSide($sUrl)
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $oHTML = new DOMDocument();
        $oHTML->loadHTML($this->fetchPage('http://gatherer.wizards.com' . $sUrl));
        $oXPath = new DOMXPath($oHTML);

        //Name
        $aCardDetails['name'] = $this->extractName($oXPath);

        //Extract Mana Cost
        $aCardDetails['mana_cost'] = $this->extractManaCost($oXPath);

        //Extract Converted Mana Cost
        $aCardDetails['converted_mana_cost'] = $this->extractedConvertedManaCost($oXPath);

        //Extract Types string
        $sTypes = $this->extractTypes($oXPath);
        $aCardDetails['supertypes'] = $this->calculateSuperType($sTypes);
        $aCardDetails['types'] = $this->calculateTypes($sTypes);
        $aCardDetails['subtypes'] = $this->calculateSubTypes($sTypes);

        //Ability Text
        $aCardDetails['text'] = $this->extractAbilityText($oXPath);

        //Extract Set
        $aCardDetails['expansion'] = $this->extractExpansion($oXPath);

        //Rarity
        $aCardDetails['rarity'] = $this->extractRarity($oXPath);

        //Versions
        $aCardDetails['versions'] = $this->extractVersions($oXPath);

        //Number
        $aCardDetails['number'] = $this->extractExpansionNumber($oXPath);

        //Artist
        $aCardDetails['artist'] = $this->extractArtist($oXPath);

        //community_rating
        $aCardDetails['community_rating']['rating'] = $this->extractCommunityRating($oXPath);

        //Total Votes
        $aCardDetails['community_rating']['votes'] = $this->extractTotalVotes($oXPath);

        return $aCardDetails;

    }


    /**
     * Determines if a card has 2 faces (In this case we are looking for a specific URL)
     *
     * @param object $oXPath
     *
     * @return boolean
     */
    public function isDoubleFaced($oXPath)
    {
        $oAlternativeSide = $oXPath->query('//a[@id="cardTextSwitchLink2"]/ancestor::div/ul/li/a/@href');
        foreach ($oAlternativeSide as $sTmp) {
            return $sTmp->nodeValue;
        }

        return false;

    }


    /**
     * Determines if the card has 2 sides or not (By looking for a specific DIV)
     *
     * @param object $oXPathContent
     *
     * @return boolean
     */
    protected function isDoubleSided($oXPathContent)
    {
        $oXPathResult = $oXPathContent->query('//div[@id="' . self::sDivPrefix . '_ctl05_nameRow"]/div[@class="value"]');
        foreach ($oXPathResult AS $sResult) {
            return true;
        }

        return false;

    }


    /**
     * Looks at our card type to determine if the card is a planeswalker
     *
     * @param string $sType The card type
     *
     * @return boolean
     */
    protected function isPlaneswalker($sType = '')
    {
        $sType = strtolower(trim($sType));
        return ($sType == 'planeswalker');

    }


    /**
     * Extracts the card name, using the existing XPath resource
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractName($oXPath)
    {
        $aCardName = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_nameRow"]/div[@class="value"]');
        foreach ($aCardName as $sTmp) {
            return trim($sTmp->nodeValue);
        }

        return;

    }


    /**
     * Extracts the mana cost of the card, using the existing XPath Resource
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractManaCost($oXPath)
    {
        $aCardDetails = array();
        $aXPathManaSymbols = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_manaRow"]/div[@class="value"]/img/@alt');
        foreach ($aXPathManaSymbols as $sTmp) {

            $sSymbolWithBrace = '{' . trim($sTmp->nodeValue) . '}';

            if (isset($this->aSymbols[$sSymbolWithBrace])) {
                $aCardDetails[] = '{' . $this->aSymbols[$sSymbolWithBrace] . '}';
            } elseif (is_numeric(trim($sTmp->nodeValue))) {
                $aCardDetails[] = '{' . trim($sTmp->nodeValue) . '}';
            } else {
                throw new Exception('Found new mana: ' . $sTmp->nodeValue);
            }
        }

        return $aCardDetails;

    }


    /**
     * Extracts the cards converted mana cost
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractedConvertedManaCost($oXPath)
    {
        $aXPathCMC = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_cmcRow"]/div[@class="value"]');
        foreach ($aXPathCMC as $sTmp) {
            return (float) trim($sTmp->nodeValue);
        }

        return;

    }


    /**
     * Extracts the card 'type', i.e: planeswalker
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractTypes($oXPath)
    {
        //Extract Types
        $aXPathTypes = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_typeRow"]/div[@class="value"]');
        foreach ($aXPathTypes as $sTmp) {
            return @iconv("UTF-8", "ASCII//TRANSLIT", trim((string) $sTmp->nodeValue));
            break;
        }

        return;

    }


    /**
     * Extracts the loyalty of the card
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractLoyalty($oXPath)
    {
        $aXPathLoyalty = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_ptRow"]/div[@class="value"]');
        foreach ($aXPathLoyalty as $sTmp) {
            return (int) trim($sTmp->nodeValue);
        }

        return;

    }


    /**
     * Extracts the toughness of card
     *
     * @param object $oXPath
     *
     * @return mixed string|array
     */
    protected function extractPowerToughness($oXPath)
    {
        $aXPathPT = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_ptRow"]/div[@class="value"]');
        foreach ($aXPathPT as $sTmp) {
            return explode(' / ', trim($sTmp->nodeValue));
        }

        return;

    }


    /**
     * Extracts the different version IDs of the card
     *
     * @param object $oXPath
     *
     * @return array
     */
    protected function extractVersions($oXPath)
    {
        $aVersions = array();
        $aXPathSets = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_otherSetsRow"]/div[@class="value"]/div[@id="' . self::sDivPrefix . $this->sPrefix . '_otherSetsValue"]/a/@href');
        foreach ($aXPathSets as $sTmp) {
            $aVersions[] = (int) trim(str_replace('Details.aspx?multiverseid=', '', $sTmp->nodeValue));
        }

        return $aVersions;

    }


    /**
     * Extracts the community rating of the card
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractCommunityRating($oXPath)
    {
        $aXPathRating = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_currentRating_textRatingContainer"]/span[@id="' . self::sDivPrefix . $this->sPrefix . '_currentRating_textRating"]');
        foreach ($aXPathRating as $sTmp) {
            return (float) trim($sTmp->nodeValue);
            break;
        }

        return;

    }


    /**
     * Extracts the total votes of the card
     *
     * @param object $oXPath
     *
     * @return int
     */
    protected function extractTotalVotes($oXPath)
    {
        $aXPathVotes = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_currentRating_textRatingContainer"]/span[@id="' . self::sDivPrefix . $this->sPrefix . '_currentRating_totalVotes"]');
        foreach ($aXPathVotes as $sTmp) {
            return (int) trim($sTmp->nodeValue);
            break;
        }

        return;

    }


    /**
     * Extracts the cards ability text
     *
     * @param object $oXPath
     *
     * @return array
     */
    protected function extractAbilityText($oXPath)
    {
        $aText = $aMatches = array();
        $aXPathText = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_textRow"]/div[@class="value"]/div[@class="cardtextbox"]');
        foreach ($aXPathText as $sTmp) {
			$sLine = trim($this->nodeContent($sTmp));

            //Assume its a land card, with only the land symbol
            if (strlen($sLine) === 1) {
                $sLine = '{' . $sLine . '}';
            } else {
                //<img src="/Handlers/Image.ashx?size=small&amp;name=W&amp;type=symbol" alt="White" align="absbottom">
                $sSearch = '/<img src="[^"]+" alt="([^"]+)" align="absbottom">/';
                $sReplace = '{$1}';
                $sLine = preg_replace($sSearch, $sReplace, $sLine);

                //Replace extracted symbols with common symbols
                $sLine = preg_replace(array_keys($this->aSymbols), array_values($this->aSymbols), $sLine);
            }
            
            $aText[] = $sLine;
        }

        return $aText;

    }


    /**
     * Extracts the cards flavor text
     *
     * @param object $oXPath
     *
     * @return array
     */
    protected function extractFlavorText($oXPath)
    {
        $aFlavorAttribution = array();
        $aXPathFlavorAttribution = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_FlavorText"]/div[@class="cardtextbox"]');
        foreach ($aXPathFlavorAttribution as $sTmp) {
            $aFlavorAttribution[] = trim($sTmp->nodeValue);
        }

        return $aFlavorAttribution;

    }


    /**
     * Extracts the color indicator (if present, of the card)
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractColorIndicator($oXPath)
    {
        $aXPathColorIndicator = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_colorIndicatorRow"]/div[@class="value"]');
        foreach ($aXPathColorIndicator as $sTmp) {
            return explode(', ', trim($sTmp->nodeValue));
        }

        return;

    }


    /**
     * Extracts the watermark of the card
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractWatermark($oXPath)
    {
        $aXPathWatermark = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_markRow"]/div[@class="value"]');
        foreach ($aXPathWatermark as $sTmp) {
            return trim($sTmp->nodeValue);
        }

        return;

    }


    /**
     * Extracts the expansion(set) of the card
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractExpansion($oXPath)
    {
        $aXPathSet = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_setRow"]/div[@class="value"]');
        foreach ($aXPathSet as $sTmp) {
            return trim($sTmp->nodeValue);
        }

        return;

    }


    /**
     * Extracts the card rarity
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractRarity($oXPath)
    {
        $aXPathRarity = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_rarityRow"]/div[@class="value"]');
        foreach ($aXPathRarity as $sTmp) {
            return trim($sTmp->nodeValue);
            break;
        }

        return;

    }


    /**
     * Extracts the cards expansion(set) number
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractExpansionNumber($oXPath)
    {
        $aXPathCardNo = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_numberRow"]/div[@class="value"]');
        foreach ($aXPathCardNo as $sTmp) {
            return trim($sTmp->nodeValue);
        }

        return;

    }


    /**
     * Extract the artist for the card
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractArtist($oXPath)
    {
        $aXPathArtist = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_artistRow"]/div[@class="value"]');
        foreach ($aXPathArtist as $sTmp) {
            return trim($sTmp->nodeValue);
        }
        
        return;

    }


    /**
     * Extracts the rulings for the card
     *
     * @param object $oXPath
     *
     * @return array
     */
    protected function extractRulings($oXPath)
    {
        $aXPathRulings = $oXPath->query('//div[@id="' . self::sDivPrefix . $this->sPrefix . '_rulingsContainer"]/table/tr');
        $aRulings = '';
        $iRuling = 0;
        foreach ($aXPathRulings as $sTmp) {
            $aXPathRowColumn = $oXPath->query('./td', $sTmp);
            $x = 0;
            foreach ($aXPathRowColumn AS $sTD) {
                if ($x === 0) {
                    list($d, $m, $y) = explode('/', $sTD->nodeValue);
                    $aRulings[$iRuling]['date'] = mktime(0, 0, 0, $m, $d, $y);
                } else {
                    $aRulings[$iRuling]['rule'] = $sTD->nodeValue;
                }
                $x++;
            }
            $iRuling++;
        }

        return $aRulings;

    }


    /**
     * Extracts the current Card ID
     *
     * @param object $oXPath
     *
     * @return string
     */
    protected function extractCardID($oXPath)
    {
        $aXPathCardID = $oXPath->query('//a[@id="' . self::sDivPrefix . $this->sPrefix . '_discussionLink"]/@href');
        foreach ($aXPathCardID as $sTmp) {
            return (int) trim(str_replace('/Pages/Card/Discussion.aspx?multiverseid=', '', $sTmp->nodeValue));
        }
        
        return;

    }


    /**
     * Calculate the Card SuperType, if it has one
     *
     * @param string $sType The card type, with international characters.
     *
     * @return string
     */
    protected function calculateSuperType($sType)
    {
        $sType = @iconv("UTF-8", "ASCII//TRANSLIT", $sType);
        $aTypes = explode(' - ', $sType);

        if (count($aTypes) < 2) {
            return '';
        }

        $aPrimaryTypes = explode(' ', $aTypes[0]);

        foreach ($aPrimaryTypes AS $sPossibleSupertype) {
            if (in_array($sPossibleSupertype, $this->aSupertypes)) {
                return $sPossibleSupertype;
            }
        }

        return;

    }


    /**
     * Calculate the Card Type, everything before the first ' - '
     *
     * @param string $sType The card type, with international characters.
     *
     * @return string
     */
    protected function calculateTypes($sType)
    {
        $sType = @iconv("UTF-8", "ASCII//TRANSLIT", $sType);
        $aTypes = explode(' - ', $sType);

        foreach ($this->aSupertypes AS $sSuperType) {
            $aTypes[0] = str_replace($sSuperType, '', $aTypes[0]);
        }

        return trim($aTypes[0]);

    }


    /**
     * Calculate a cards potential subtypes, based on the entire type string
     *
     * @param string $sSubtype The subtype string, including the type.
     *
     * @return array
     */
    protected function calculateSubTypes($sSubtype)
    {
        if (strlen($sSubtype) === 0) {
            return array();
        }

        $sSubtype = @iconv("UTF-8", "ASCII//TRANSLIT", $sSubtype);
        $aTmp = explode(' - ', $sSubtype);

        if (!isset($aTmp[1])) {
            return array();
        }

        return explode(' ', $aTmp[1]);

    }


    /**
     * Fetches a specific URLs content and returns the data
     *
     * @param string $sUrl The URL we want to fetch
     * @return string The pages content
     * @throws Exception
     */
    protected function fetchPage($sUrl)
    {
        $rHandle = curl_init();
        curl_setopt($rHandle, CURLOPT_URL, $sUrl);
        curl_setopt($rHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($rHandle, CURLOPT_CONNECTTIMEOUT, 5);
        $data = curl_exec($rHandle);
        curl_close($rHandle);

        if (strlen($data) === 0) {
            throw new Exception('No data returned from: ' . $sUrl);
        }

        return $data;

    }


    /**
     * Extracts the content of a node, includin any HTML
     * Borrowed from: http://php.net/manual/en/book.dom.php#89802
     *
     * @param object $oNode
     * @param booleam $bOuter
     * @return string
     */
    protected function nodeContent($oNode, $bOuter = false)
    {
        $oDom = new DOMDocument('1.0');
        $b = $oDom->importNode($oNode->cloneNode(true), true);
        $oDom->appendChild($b);
        $h = $oDom->saveHTML();

        if (!$bOuter) {
            $h = substr($h, strpos($h, '>') + 1, -(strlen($oNode->nodeName) + 4));
        }

        return $h;

    }


}

//$oTutor = new TutorPHP();
//var_dump($oTutor->fetchCard(194123)); //Watermark
//var_dump($oTutor->fetchCard(130680)); //Color Indicator
//var_dump($oTutor->fetchCard(158903)); //Versions
//var_dump($oTutor->fetchCard(292976)); //Dual Face (Life/Death)
//var_dump($oTutor->fetchCard(262875)); //Double Sided
//var_dump($oTutor->fetchCard(262675)); //Dual Face with left RUling
//var_dump($oTutor->fetchCard(192218)); //Planeswalker
//var_dump($oTutor->fetchCard(23040)); //Single Side
//var_dump($oTutor->fetchCard(23040)); //Single Side
//var_dump($oTutor->fetchSetCardIDs('Archenemy')); //Dual Face
//var_dump($oTutor->fetchCardLegalities(29947)); //Card Legalities
//die();

?>
