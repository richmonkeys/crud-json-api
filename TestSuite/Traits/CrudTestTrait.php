<?php
namespace Crud\TestSuite\Traits;

use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
use Crud\Event\Subject;

/**
 * Utility methods for easier testing with Crud in CakePHP & PHPUnit
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
trait CrudTestTrait {

/**
 * Reference to the final CRUD event subject after full event cycle
 *
 * @var \Crud\Event\Subject
 */
	protected $_subject;

/**
 * Subscribe to Crud.beforeRender and Crud.beforeRedirect events
 *
 * This is the two 'final' emitted events after a CRUD life cycle,
 * and thus will hold the final object
 *
 * It's stored in the `$this->_subject` property
 *
 * @param  \Cake\Controller\Controller $controller
 * @return void
 */
	public function _subscribeToEvents(Controller $controller = null) {
		if ($controller === null) {
			$controller = $this->controller;
		}

		$controller->Crud->on('beforeRender', function($event) {
			$this->_subject = $event->subject;
		});

		$controller->Crud->on('beforeRedirect', function($event) {
			$this->_subject = $event->subject;
		});
	}

/**
 * Get a "model" (Table) instance
 *
 * @param  string $class   Full table class name
 * @param  mixed  $methods Methods to mock
 * @param  string $alias   Table alias / name
 * @param  string $table   Table name in the database
 * @return \Cake\ORM\Table
 */
	public function getModel($class, $methods, $alias, $table) {
		$mock = $this->getMockBuilder($class)
			->setMethods($methods)
			->setConstructorArgs([['alias' => $alias, 'table' => $table]])
			->getMock();
		$mock->connection(ConnectionManager::get('test'));
		return $mock;
	}

/**
 * Assert a redirect happened
 *
 * `$actual` can be a string, Controller or Response instance
 *
 * @param  string $expected
 * @param  mixed  $actual
 * @return void
 */
	public function assertRedirect($expected, $actual = null) {
		if ($actual === null) {
			$actual = $this->controller;
		}

		if ($actual instanceof Controller) {
			$actual = $actual->response->location();
		}

		if ($actual instanceof Response) {
			$actual = $actual->location();
		}

		if (empty($actual)) {
			throw new \Exception('assertRedirect: Expected "actual" to be a non-empty string');
		}

		$this->assertEquals($expected, $actual,	'Was not redirected to ' . $expected);
	}

/**
 * Assert these CRUD events was emitted during the life cycle
 *
 * The `$expected` list do not need to prefix events with `Crud.` - this is done
 * automatically before comparison
 *
 * @param  array $expected An array of CRUD events we expected to be fired
 * @param  array $actual   Can be an Event class, Crud subject or array with event names
 * @return void
 */
	public function assertEvents(array $expected, array $actual = null) {
		if ($actual === null) {
			$actual = $this->_subject;
		}

		if ($actual instanceof Event) {
			$actual = $actual->subject->getEvents();
		}

		if ($actual instanceof Subject) {
			$actual = $actual->getEvents();
		}

		if (empty($actual)) {
			throw new \Exception('assertEvents: Expected actual to be not-empty');
		}

		if (!is_array($actual)) {
			throw new \Exception('assertEvents: Expected actual to be an array');
		}

		foreach ($expected as &$key) {
			if (false !== strpos($key, '.')) {
				continue;
			}

			$key = 'Crud.' . $key;
		}

		$this->assertEquals($expected, $actual, 'Not all expected events was fired');
	}

}
