<?php

/**
 * Tests du bon fonctionnement du module CustomerAutoGroups
 *
 * @author hhennes <contact@h-hennes.fr>
 */

//@ToDo : Pour l'instant le path d'inclusion est déterminé par le chemin d'exécution
//Il faudra optimiser ce point, car les tests doivent être lancés de la manière suivante
// phpunit Tests/Unit/CustomerAutoGroupsTest.php à la racine du dossier du module

#$exec_dir = str_replace('modules/customerautogroups','',trim(shell_exec('pwd')));
include_once dirname(__FILE__).'/../../../../config/config.inc.php';
include_once _PS_MODULE_DIR_ . '/customerautogroups/classes/AutoGroupRule.php';

class CustomerAutoGroupsTest extends PHPUnit_Framework_TestCase {

    //Nom du module
    protected $_moduleName = 'customerautogroups';

    //Tab admin du module
    protected $_moduleTabName = 'Rules';

    /**
     * Avant l'exécution de la classe
     * Mise à jour de la config + suppression des données existantes
     */
    public static function setUpBeforeClass() {
        self::initConfig();
        self::cleanAllCustomCustomersGroup();
        self::cleanAutoGroupsRules();
    }

    /**
     * Vérification que le module est installé (via la méthode prestashop)
     * @group customerautogroups_install
     */
    public function testModuleIsInstalled() {
        $this->assertTrue(Module::isInstalled($this->_moduleName));
    }

    /**
     * On vérifie que le module est bien greffé sur les nouveaux hooks
     * @depends testModuleIsInstalled
     * @group customerautogroups_install
     */
    public function testModuleIsHooked() {

        //Instanciation du module
        $moduleInstance = Module::getInstanceByName($this->_moduleName);

        $this->assertNotFalse($moduleInstance->isRegisteredInHook('actionCustomerAccountAdd'));
    }

    /**
     * Vérifie que la tab du back office est bien installée
     * @group eicmslinks_install
     */
    public function testInstallTab(){
        $id_tab = Tab::getIdFromClassName($this->_moduleTabName);
        $this->assertNotFalse($id_tab);
    }

    //@ToDO : Vérfier la bonne présence des bases de données

    /**
     * Test de creation d'une règle
     * @group rules
     * @param array $rule
     * @dataProvider getAutoGroupRules
     */
    public function testcreateAutoGroupRule($rule) {
				
        //Création de la nouvelle règle
        $ruleModel = new AutoGroupRule();

        $languages = Language::getLanguages(true);
        foreach ($languages as $lang) {
            $ruleModel->name[$lang['id_lang']] = $rule['name'];
            $ruleModel->description[$lang['id_lang']] = $rule['description'];
        }

        $ruleModel->condition_type = $rule['condition_type'];
        $ruleModel->condition_field = $rule['condition_field'];
        $ruleModel->condition_operator = $rule['condition_operator'];
        $ruleModel->condition_value = $rule['condition_value'];
        $ruleModel->priority = $rule['priority'];
        $ruleModel->active = $rule['active'];
        $ruleModel->stop_processing = $rule['stop_processing'];
        $ruleModel->default_group = $rule['default_group'];
        $ruleModel->clean_groups = $rule['clean_groups'];

        //Gestion de la creation du groupe de destination
        $ruleModel->id_group = $this->_getCustomerGroupId($rule['customer_group_name']);

        //Sauvegarde de la nouvelle règle
        try {
            $ruleModel->save();
        } catch (PrestaShopException $e) {
            $this->fail('Erreur sauvegarde règle ' . $e->getMessage());
            return;
        }

        //La règle est enregistrée, on la charge depuis la bdd et on vérifie que les champs sont ok
        $ruleModelDb = new AutoGroupRule($ruleModel->id);

        $this->assertEquals($ruleModelDb->name[1], $rule['name']);
        $this->assertEquals($ruleModelDb->description[1], $rule['description']);
        $this->assertEquals($ruleModelDb->condition_type, $rule['condition_type']);
        $this->assertEquals($ruleModelDb->condition_field, $rule['condition_field']);
        $this->assertEquals($ruleModelDb->condition_operator, $rule['condition_operator']);
        $this->assertEquals($ruleModelDb->condition_value, $rule['condition_value']);
        $this->assertEquals($ruleModelDb->priority, $rule['priority']);
        $this->assertEquals($ruleModelDb->active, $rule['active']);
        $this->assertEquals($ruleModelDb->stop_processing, $rule['stop_processing']);
        $this->assertEquals($ruleModelDb->default_group, $rule['default_group']);
        $this->assertEquals($ruleModelDb->clean_groups, $rule['clean_groups']);
    }

    /**
	 * Dataprovider get Rules tests
	 */
	public function getAutoGroupRules() {
		
		$customerFileName = dirname(__FILE__).'/fixtures/rules.yml';
		
		$yml = new PHPUnit_Extensions_Database_DataSet_YamlDataSet($customerFileName);
		$datas = array();
        $ymlDatas = $yml->getTable('datas');

		for ($i = 0; $i < $ymlDatas->getRowCount(); $i++) {
			$datas[] = array($ymlDatas->getRow($i));
		}
		
		return $datas;
	}
	
	/**
     * Dataprovider des données de test pour les règle
     */
    public function getAutoGroupRulesStatic() {

        return array(
            array('rule_us' => array(
                    'name' => 'US group auto', // Nom de la règle
                    'description' => 'Auto groups for us customers', //Description de la règle
                    'condition_type' => 2, // Type de condition 1 customer / 2 addresse
                    'condition_field' => 'id_country', //Champ condition
                    'condition_operator' => 'eq', // Operateur
                    'condition_value' => '21', // Valeur du champ
                    'customer_group_name' => 'us_group', // Groupe a assigner à l'utilisateur ( créé automatiquement )
                    'priority' => 0, //Priorité règle 0 Haute 10 Basse
                    'active' => 1, //Règle active 1 / Inactive 0
                    'stop_processing' => 1, //Arrêter de traiter les règles suivantes 1 Oui / 0 Non
                    'default_group' => 1, // Définir comme group par défaut pour l'utilisateur 1 Oui / 0 Non
                    'clean_groups' => 1, // Supprimer tous les autres groupes de l'utilisateur 1 Oui / 0 Non
                )),
            array('rule_fr' => array(
                    'name' => 'FR group auto',
                    'description' => 'Auto groups for french customers',
                    'condition_type' => 2, //1 customer , 2 addresse
                    'condition_field' => 'id_country',
                    'condition_operator' => 'eq',
                    'condition_value' => '8',
                    'customer_group_name' => 'fr_group',
                    'priority' => 0,
                    'active' => 1,
                    'stop_processing' => 1,
                    'default_group' => 1,
                    'clean_groups' => 1,
                )),
            array('male_users' => array(
                    'name' => 'Males user',
                    'description' => 'Auto groups for male users',
                    'condition_type' => 1, //1 customer , 2 addresse
                    'condition_field' => 'id_gender',
                    'condition_operator' => 'eq',
                    'condition_value' => '1',
                    'customer_group_name' => 'male_users',
                    'priority' => 1,
                    'active' => 1,
                    'stop_processing' => 0,
                    'default_group' => 0,
                    'clean_groups' => 0,
                )),
           array('gmail_users' => array(
                    'name' => 'gmail user',
                    'description' => 'Test for like condition',
                    'condition_type' => 1, //1 customer , 2 addresse
                    'condition_field' => 'email',
                    'condition_operator' => 'LIKE %',
                    'condition_value' => '@gmail.com',
                    'customer_group_name' => 'gmail_users',
                    'priority' => 1,
                    'active' => 1,
                    'stop_processing' => 0,
                    'default_group' => 0,
                    'clean_groups' => 0,
                ))
            );
    }


    /**
     * Tests de la bonne assignation
     * @dataProvider getCustomers
     * @param array $customerDatas
     */
    public function testAutoAssignCustomerToGroup($customerDatas) {

        //Création du nouveau client ( et adresse si nécessaire )
        $customer = $this->_createCustomer($customerDatas);

        //Exécution du hook dans lequel les données sont traitées
        Hook::exec('actionCustomerAccountAdd', array('newCustomer' => $customer));

        //On recharge les informations du client depuis la bdd (après le passage dans le hook )
        $customerDb = new Customer($customer->id);

        //On récupère les identifiants des groupes dans lequel le client doit etre présent
        $customerGroups = array();
        foreach( $customerDatas['expected_groups'] as $group ) {
            if ( $group == 'default'){
                $customerGroups[] = 3;
            }
            else {
                $id_group = $this->_getCustomerGroupId($group);
                $customerGroups[]= $id_group;
            }
        }

        //On récupère les groupes du clients
        $groups = $customerDb->getGroups();

        //On s'assure que les groupes du client correspondent à ceux choisis
        $this->assertEquals($groups,$customerGroups);

        //Si on veut verifier le groupe par défaut du client
        if (array_key_exists('default_group', $customerDatas)) {
            if ( $customerDatas['default_group'] == 'default')
                $defaultGroup = 3;
            else
                $defaultGroup = $this->_getCustomerGroupId($group);

            $this->assertEquals($defaultGroup,$customerDb->id_default_group);
        }
    }

    /**
     * Dataprovider des données de test pour les clients
     * (Statique)
     */
    public function getCustomers(){

        return array(
            array('customer_us' => array(
                'id_gender' => 1,
                'firstname' => 'herve',
                'lastname' => 'herve US',
                'email' => sprintf("test%s@test.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => 'Manathan',
                'address_address2' => '',
                'address_postcode' => '20000',
                'address_city' => 'New York',
                'address_id_country' => 21,
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('us_group'),
                'default_group' => 'us_group',
            )),
            array('customer_fr' => array(
                'id_gender' => 1,
                'firstname' => 'herve',
                'lastname' => 'herve FR',
                'email' => sprintf("test%s@test.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => '16 rue des tests',
                'address_address2' => '',
                'address_postcode' => '67000',
                'address_city' => 'Strasbourg',
                'address_id_country' => 8,
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('fr_group'),
                'default_group' => 'fr_group',
            )),
            array('customer_male' => array(
                'id_gender' => 1,
                'firstname' => 'herve',
                'lastname' => 'male',
                'email' => sprintf("testmale%s@test.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => '16 rue des tests',
                'address_address2' => '',
                'address_postcode' => '67000',
                'address_city' => 'Strasbourg',
                'address_id_country' => 15, //Pas france , ni us
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('default','male_users'),
                'default_group' => 'default',
            )),
            array('customer_gmail' => array(
                'id_gender' => 2,
                'firstname' => 'herve',
                'lastname' => 'gmail',
                'email' => sprintf("test%s@gmail.com",time()),
                'password' => 'test2015',
                'add_address' => 1,
                'address_firstname' => 'herve',
                'address_lastname' => 'herve',
                'address_address1' => '16 rue des tests',
                'address_address2' => '',
                'address_postcode' => '67000',
                'address_city' => 'Strasbourg',
                'address_id_country' => 15, //Pas france , ni us
                'address_id_state' => 0,
                'address_phone' => '0836656565',
                'expected_groups' => array('default','gmail_users'),
                'default_group' => 'default',
            ))
        );

    }

    /**
     * Création d'un client
     * @param array $datas
     * @return Customer $customer
     */
    protected function _createCustomer($datas) {

        $customer = new Customer();
        $customer->firstname = $datas['firstname'];
        $customer->lastname = $datas['lastname'];
        $customer->id_gender = $datas['id_gender'];
        $customer->email = $datas['email'];
        $customer->passwd = ToolsCore::encrypt($datas['password']);
        //Données par défaut
        $customer->id_default_group = 3;

        try {
            $customer->save();
        } catch (PrestaShopException $e) {
            echo $e->getMessage();
        }

        //Création de l'adresse si spécifié
        if ($datas['add_address'] == 1) {
            $address = new Address();
            $address->firstname = $datas['address_firstname'];
            $address->lastname = $datas['address_lastname'];
            $address->address1 = $datas['address_address1'];
            $address->address2 = $datas['address_address2'];
            $address->postcode = $datas['address_postcode'];
            $address->city = $datas['address_city'];
            $address->phone = $datas['address_phone'];
            $address->id_country = $datas['address_id_country'];
            $address->id_state = $datas['address_id_state'];
            $address->id_customer = $customer->id;
            $address->alias = 'Automatic address';

            try {
                $address->save();
            } catch (PrestaShopException $e) {
                echo $e->getMessage();
            }
        }

        return $customer;
    }

    /**
     * Récupération de l'identifiant du groupe client
     * ( Création d'un groupe si nécessaire )
     * @param string $name : Nom du groupe
     * @return int Identifiant prestashop du groupe
     */
    protected function _getCustomerGroupId($name) {

        $group = Group::searchByName($name);

        if (!$group) {
            $newgroup = new Group();
            $languages = Language::getLanguages(true);
            foreach ($languages as $lang)
                $newgroup->name[$lang['id_lang']] = $name;
            $newgroup->reduction = 0;
            $newgroup->price_display_method = 1;
            $newgroup->show_prices = 1;

            try {
                $newgroup->save();
            } catch (PrestaShopException $e) {
                $this->fail('Erreur creation du groupe ' . $e->getMessage());
                exit();
            }
            return (int)$newgroup->id;
        } else {
            return (int)$group['id_group'];
        }
    }

    /**
     * Mise en place de la configuration nécessaire au module
     */
    public static function initConfig() {
        //Activation de l'inscription avec les adresses
        if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE') == 1)
            Configuration::set('PS_REGISTRATION_PROCESS_TYPE', 1);
    }



    /**
     * Suppression de tous les groupes clients non standards
     */
    public static function cleanAllCustomCustomersGroup() {

        $groups = Group::getGroups(1);
        foreach ($groups as $group) {
            if ($group['id_group'] > 3) {
                $groupModel = new Group($group['id_group']);
                try {
                    $groupModel->delete();
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }

    /**
     * Suppression de toutes les règles autoGroups
     */
    public static function cleanAutoGroupsRules(){
        Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."autogroup_rule");
        Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."autogroup_rule_lang");
    }
}
