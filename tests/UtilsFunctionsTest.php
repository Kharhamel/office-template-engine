<?php


namespace tests;


use PHPUnit\Framework\TestCase;

class UtilsFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__.'/../src/lib/utils.php';
    }

    public function testCheckArgList(): void
    {
        [$x, $args] = CheckArgList('test');
        $this->assertEquals('test', $x);
        $this->assertEquals([], $args);
    }

    public function testCheckArgList2(): void
    {
        [$x, $args] = CheckArgList('test(foo)');
        $this->assertEquals('test', $x);
        $this->assertEquals(['foo'], $args);
    }

    public function testCheckArgList3(): void
    {
        [$x, $args] = CheckArgList('test(foo,lol,faa)');
        $this->assertEquals('test', $x);
        $this->assertEquals(['foo', 'lol', 'faa'], $args);
    }

}