<?php

class TutorPHPTest extends PHPUnit_Framework_TestCase
{

    protected $oTutorPHP;


    public function setUp()
    {
        $this->oTutorPHP = new TutorPHP();

    }


    public function testFetchSetCardIDsException()
    {

        try {
            $this->oTutorPHP->fetchSetCardIDs();
        } catch (Exception $expected) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('An expected exception has not been raised.');

    }


    public function testfetchCardException()
    {
        try {
            $this->oTutorPHP->fetchCard();
        } catch (Exception $expected) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('An expected exception has not been raised.');

    }


    public function testfetchCardLegalitiesException()
    {
        try {
            $this->oTutorPHP->fetchCardLegalities();
        } catch (Exception $expected) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('An expected exception has not been raised.');

    }


    public function testfetchCardPlaneswalker()
    {

        $aCard = $this->oTutorPHP->fetchCard(192218);
        $this->assertTrue($aCard['name'] == 'Gideon Jura');

    }


    public function testfetchCardLegalities()
    {
        $aLegalities = $this->oTutorPHP->fetchCardLegalities(192218);
        $this->assertTrue(count($aLegalities) === 12);

    }


    public function testFetchCardLegality()
    {
        $aLegalities = $this->oTutorPHP->fetchCardLegalities(29947);
        $this->assertTrue($aLegalities[9]['Conditions'] === 'Banned as Commander');

    }


    public function testExtractDualFace()
    {

        $aCard = $this->oTutorPHP->fetchCard(262675);
        $this->assertTrue($aCard["name"] === 'Afflicted Deserter' && $aCard['alternate_side']['name'] === 'Werewolf Ransacker');

    }


    public function testExtractDualSide()
    {
        $aCard = $this->oTutorPHP->fetchCard(292976);
        $this->assertTrue($aCard['name'] === 'Death' && $aCard['alternate_face']['name'] === 'Life');

    }


    public function testBasicLandWrapper()
    {
        $aCard = $this->oTutorPHP->fetchCard(249728);
        var_dump($aCard);
        $this->assertTrue($aCard['text'][0] === '{R}');

    }


}
