<?php

namespace SMW\Test;

use SMW\SimpleDependencyBuilder;
use SMW\DependencyContainer;
use SMW\DataValueFactory;
use SMW\StoreFactory;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Settings;

use RequestContext;
use FauxRequest;
use WebRequest;
use Language;
use Title;

use ReflectionClass;

use SMWDataItem;

/**
 * @codeCoverageIgnore
 *
 * Class contains general purpose methods
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class SemanticMediaWikiTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns the name of the deriving class being tested
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public abstract function getClass();

	/**
	 * Helper method that returns a MockObjectBuilder object
	 *
	 * @since 1.9
	 *
	 * @return MockObjectBuilder
	 */
	public function newMockBuilder() {

		$builder = new MockObjectBuilder();
		$builder->registerRepository( new CoreMockObjectRepository() );
		$builder->registerRepository( new MediaWikiMockObjectRepository() );

		return $builder;
	}

	/**
	 * Helper method that returns a SimpleDependencyBuilder object
	 *
	 * @since 1.9
	 *
	 * @param DependencyContainer $dependencyContainer
	 *
	 * @return SimpleDependencyBuilder
	 */
	public function newDependencyBuilder( DependencyContainer $dependencyContainer = null ) {
		return new SimpleDependencyBuilder( $dependencyContainer );
	}

	/**
	 * Helper method that returns a ReflectionClass object
	 *
	 * @since 1.9
	 *
	 * @param string|null $class
	 *
	 * @return ReflectionClass
	 */
	public function newReflector( $class = null ) {
		return new ReflectionClass( $class === null ? $this->getClass() : $class );
	}

	/**
	 * Helper method that returns a randomized Title object to avoid results
	 * are influenced by cross instantiated objects with the same title name
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 * @param $text|null
	 *
	 * @return Title
	 */
	protected function newTitle( $namespace = NS_MAIN, $text = null ) {
		return Title::newFromText( $text === null ? $this->newRandomString() : $text, $namespace );
	}

	/**
	 * Helper method that returns a User object
	 *
	 * @since 1.9
	 *
	 * @return User
	 */
	protected function getUser() {
		return $this->newMockUser();
	}

	/**
	 * Helper method that returns a User object
	 *
	 * @since 1.9
	 *
	 * @return User
	 */
	protected function newMockUser() {
		return new MockSuperUser();
	}

	/**
	 * Helper method that returns a Language object
	 *
	 * @since 1.9
	 *
	 * @return Language
	 */
	protected function getLanguage( $langCode = 'en' ) {
		return Language::factory( $langCode );
	}

	/**
	 * Returns RequestContext object
	 *
	 * @param array $params
	 *
	 * @return RequestContext
	 */
	protected function newContext( $request = array() ) {

		$context = new RequestContext();

		if ( $request instanceof WebRequest ) {
			$context->setRequest( $request );
		} else {
			$context->setRequest( new FauxRequest( $request, true ) );
		}

		$context->setUser( new MockSuperUser() );

		return $context;
	}

	/**
	 * Helper method that returns a randomized DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 *
	 * @return DIWikiPage
	 */
	protected function getSubject( $namespace = NS_MAIN ) {
		return DIWikiPage::newFromTitle( $this->newTitle( $namespace ) );
	}

	/**
	 * Helper method that returns a DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @param Title|null $title
	 *
	 * @return DIWikiPage
	 */
	protected function newSubject( Title $title = null ) {
		return DIWikiPage::newFromTitle( $title === null ? $this->newTitle() : $title );
	}

	/**
	 * Helper method that returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 *
	 * @return Settings
	 */
	protected function newSettings( array $settings = array() ) {
		return Settings::newFromArray( $settings );
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 * @param $prefix identify a specific random string during testing
	 *
	 * @return string
	 */
	protected function newRandomString( $length = 10, $prefix = null ) {
		return $prefix . ( $prefix ? '-' : '' ) . substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Helper method to skip the test if it is not a SQLStore
	 *
	 * @since 1.9
	 */
	protected function runOnlyOnSQLStore( $store = null ) {

		if ( $store === null ) {
			$store = StoreFactory::getStore();
		}

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable to SMWSQLStore3' );
		}

	}

	protected function getStore() {
		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable for SMWSQLStore3' );
		}

		return $store;
	}

	/**
	 * Utility method taking an array of elements and wrapping
	 * each element in it's own array. Useful for data providers
	 * that only return a single argument.
	 *
	 * @see MediaWikiTestCase::arrayWrap
	 *
	 * @since 1.9
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return array( $element );
			},
			$elements
		);
	}

	/**
	 * Asserts that for a given semantic container expected property / value
	 * pairs are available
	 *
	 * Expected assertion array should follow
	 * 'propertyCount' => int
	 * 'propertyLabel' => array() or 'propertyKey' => array()
	 * 'propertyValue' => array()
	 *
	 * @param SemanticData $semanticData
	 * @param array $expected
	 */
	protected function assertSemanticData( SemanticData $semanticData, array $expected, $message = null ) {

		$properties = $semanticData->getProperties();

		$this->assertCount(
			$expected['propertyCount'],
			$properties,
			'asserts whether the SemanticData container contained an expected ammount of properties'
		);

		$this->assertProperties( $semanticData, $properties, $expected );

	}

	/**
	 * Assert property
	 *
	 * @since  1.9
	 *
	 * @param  array $expected
	 * @param  DIProperty $property
	 */
	protected function assertProperties( SemanticData $semanticData, array $properties, array $expected ) {

		foreach ( $properties as $key => $diproperty ) {
			$this->assertInstanceOf( '\SMW\DIProperty', $diproperty );

			if ( isset( $expected['propertyKey']) ){
				$this->assertContains(
					$diproperty->getKey(),
					$expected['propertyKey'],
					'asserts that the SemanticData container contained a specific property key'
				);
			}

			if ( isset( $expected['propertyLabel']) ){
				$this->assertContains(
					$diproperty->getLabel(),
					$expected['propertyLabel'],
					'aasserts that the SemanticData container contained a specific property label'
				);
			}

			if ( isset( $expected['propertyValue']) ){
				$this->assertPropertyValues( $diproperty, $semanticData->getPropertyValues( $diproperty ), $expected );
			}

		}
	}

	/**
	 * Assert property values
	 *
	 * @since  1.9
	 *
	 * @param  array $expected
	 * @param  DIProperty $property
	 */
	protected function assertPropertyValues( DIProperty $property, $dataItems, array $expected ) {

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataItemValue( $dataItem, $property );
			$DItype = $dataValue->getDataItem()->getDIType();

			if ( $DItype === SMWDataItem::TYPE_WIKIPAGE ) {

				$this->assertContains(
					$dataValue->getWikiValue(),
					$expected['propertyValue'],
					'asserts that the SemanticData container contained a property value of TYPE_WIKIPAGE'
				);

			} else if ( $DItype === SMWDataItem::TYPE_NUMBER ) {

				$this->assertContains(
					$dataValue->getNumber(),
					$expected['propertyValue'],
					'asserts that the SemanticData container contained a property value of TYPE_NUMBER'
				);

			} else if ( $DItype === SMWDataItem::TYPE_TIME ) {

				$this->assertContains(
					$dataValue->getISO8601Date(),
					$expected['propertyValue'],
					'asserts that the SemanticData container contained a property value of TYPE_TIME'
				);

			} else if ( $DItype === SMWDataItem::TYPE_BLOB ) {

				$this->assertContains(
					$dataValue->getWikiValue(),
					$expected['propertyValue'],
					'asserts that the SemanticData container contained a property value of TYPE_BLOB'
				);

			}

		}
	}

}
