<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Element;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;

/**
 * Base element.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class Element implements ElementInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * Driver.
     *
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var SelectorsHandler
     */
    private $selectorsHandler;

    /**
     * Initialize element.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;

        // TODO: pass these in constructor instead of Session
        $this->driver = $session->getDriver();
        $this->selectorsHandler = $session->getSelectorsHandler();
    }

    /**
     * Returns element session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns element's driver.
     *
     * @return DriverInterface
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns selectors handler.
     *
     * @return SelectorsHandler
     */
    protected function getSelectorsHandler()
    {
        return $this->selectorsHandler;
    }

    /**
     * Checks whether element with specified selector exists.
     *
     * @param string       $selector selector engine name
     * @param string|array $locator  selector locator
     *
     * @return Boolean
     */
    public function has($selector, $locator)
    {
        return null !== $this->find($selector, $locator);
    }

    /**
     * Finds first element with specified selector.
     *
     * @param string       $selector selector engine name
     * @param string|array $locator  selector locator
     *
     * @return NodeElement|null
     */
    public function find($selector, $locator)
    {
        $items = $this->findAll($selector, $locator);

        return count($items) ? current($items) : null;
    }

    /**
     * Finds all elements with specified selector.
     *
     * @param string       $selector selector engine name
     * @param string|array $locator  selector locator
     *
     * @return NodeElement[]
     */
    public function findAll($selector, $locator)
    {
        $xpath = $this->getSelectorsHandler()->selectorToXpath($selector, $locator);
        $currentXpath = $this->getXpath();
        $expressions = array();

        // Regex to find union operators not inside brackets.
        $pattern = '/\|(?![^\[]*\])/';

        // If the parent current xpath contains a union we need to wrap it in parentheses.
        if (preg_match($pattern, $currentXpath)) {
            $currentXpath = '(' . $currentXpath . ')';
        }

        // Split any unions into individual expressions.
        foreach (preg_split($pattern, $xpath) as $expression) {
            $expression = trim($expression);
            // add parent xpath before element selector
            if (0 === strpos($expression, '/')) {
                $expression = $currentXpath.$expression;
            } else {
                $expression = $currentXpath.'/'.$expression;
            }
            $expressions[] = $expression;
        }

        $xpath = implode(' | ', $expressions);

        return $this->getDriver()->find($xpath);
    }

    /**
     * Returns element text (inside tag).
     *
     * @return string
     */
    public function getText()
    {
        return $this->getDriver()->getText($this->getXpath());
    }

    /**
     * Returns element html.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->getDriver()->getHtml($this->getXpath());
    }
}
