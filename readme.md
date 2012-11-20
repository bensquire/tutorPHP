TutorPHP
=================
TutorPHP is a simple PHP class designed to retrieve 'Magic The Gathering' card 
information from the gather website. Inspired by original and best Express based Tutor
service (https://github.com/davidchambers/tutor), I wanted to contribute something
but without having to learn a new language.


Retrieve a Card:
----------------
    $oTutorPHP = new TutorPHP_Service_TutorPHP();
    $aCard = $oTutorPHP->fetchCard(177597);

	//Returns
	array ( 'name' => 'Sigil Blessing',
			'mana_cost' => array ( 0 => '{G}', 1 => '{W}', ),
			'converted_mana_cost' => 2,
			'types' => 'Instant',
			'subtypes' => array ( ),
			'versions' => array ( 0 => 177597, 1 => 243447, ),
			'community_rating' => array ( 'rating' => 3.9, 'votes' => 60, ),
			'text' => array ( 0 => 'Until end of turn, target creature you control gets +3/+3 and other creatures you control get +1/+1.', ),
			'flavor_text' => '"For unwavering commitment and unflinching strength, the Order of the White Orchid confers its sigil. Rise, knight-captain, and do your duty."',
			'expansion' => 'Shards of Alara',
			'rarity' => 'Common',
			'number' => '195',
			'artist' => 'Matt Stewart',
			'rulings' => array ( 0 => array ( 'date' => 1199923200, 'rule' => 'If the targeted creature becomes an illegal target by the time Sigil Blessing resolves, the entire spell will be countered. Your other creatures won\'t get +1/+1.', ), ),
			'card_id' => 177597,
			'gatherer_url' => 'http://gatherer.wizards.com/Pages/Card/Details.aspx?multiverseid=177597',
			'image_url' => 'http://gatherer.wizards.com/Handlers/Image.ashx?type=card&multiverseid=177597'
		)



Retrieve a card legalities:
---------------------------
	$oTutorPHP = new TutorPHP_Service_TutorPHP();
	$aLegalities = $oTutorPHP->fetchCardLegalities(177597);

	array ( 1 => array ( 'Format' => 'Modern', 'Legality' => 'Legal', 'Conditions' => '', ), 
			2 => array ( 'Format' => 'Shards of Alara Block', 'Legality' => 'Legal', 'Conditions' => '', ), 
			3 => array ( 'Format' => 'Legacy', 'Legality' => 'Legal', 'Conditions' => '', ), 
			4 => array ( 'Format' => 'Vintage', 'Legality' => 'Legal', 'Conditions' => '', ),
			5 => array ( 'Format' => 'Freeform', 'Legality' => 'Legal', 'Conditions' => '', ),
			6 => array ( 'Format' => 'Prismatic', 'Legality' => 'Legal', 'Conditions' => '', ), 
			7 => array ( 'Format' => 'Tribal Wars Legacy', 'Legality' => 'Legal', 'Conditions' => '', ),
			8 => array ( 'Format' => 'Classic', 'Legality' => 'Legal', 'Conditions' => '', ),
			9 => array ( 'Format' => 'Singleton 100', 'Legality' => 'Legal', 'Conditions' => '', ), 
			10 => array ( 'Format' => 'Commander', 'Legality' => 'Legal', 'Conditions' => '', ))



Fetch Multiverse IDs of all cards in a set:
-------------------------------------------
	$oTutorPHP->fetchSetCardIDs('Return to Ravnica');
	array (
		0 => 253507
		1 => 253508
		2 => 253509
		3 => 253510
		4 => 253512
		5 => 253514
		6 => 253518
		7 => 253519
		8 => 253520)
	... etc


Implemented Functionality:
--------------------------
 - Retrieve a single card
 - Retrieve a cards legalities
 - Retrieve a set of card IDs


Requirements:
-------------
This library requires no additional software beyond  a functional version of PHP 5.2 (or greater).