<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) 2014-2021 Sean Tymon <tymon148@gmail.com>
 * (c) 2021 PHP Open Source Saver
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPOpenSourceSaver\JWTAuth\Test\Providers\Storage;

use Illuminate\Contracts\Cache\Repository;
use Mockery\MockInterface;
use PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate as Storage;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Test\Stubs\TaggedStorage;

class IlluminateTest extends AbstractTestCase
{
    /**
     * @var MockInterface|Repository
     */
    protected $cache;

    /**
     * @var Storage
     */
    protected $storage;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache = \Mockery::mock(Repository::class);
        $this->storage = new Storage($this->cache);
    }

    public function testItShouldAddTheItemToStorage()
    {
        $this->cache->shouldReceive('put')->with('foo', 'bar', 10)->once();

        $this->storage->add('foo', 'bar', 10);
    }

    public function testItShouldAddTheItemToStorageForever()
    {
        $this->cache->shouldReceive('forever')->with('foo', 'bar')->once();

        $this->storage->forever('foo', 'bar');
    }

    public function testItShouldGetAnItemFromStorage()
    {
        $this->cache->shouldReceive('get')->with('foo')->once()->andReturn(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->storage->get('foo'));
    }

    public function testItShouldRemoveTheItemFromStorage()
    {
        $this->cache->shouldReceive('forget')->with('foo')->once()->andReturn(true);

        $this->assertTrue($this->storage->destroy('foo'));
    }

    public function testItShouldRemoveAllItemsFromStorage()
    {
        $this->cache->shouldReceive('flush')->withNoArgs()->once();

        $this->storage->flush();
    }

    // Duplicate tests for tagged storage --------------------

    public function testItShouldAddTheItemToTaggedStorage()
    {
        $this->emulateTags();
        $this->cache->shouldReceive('put')->with('foo', 'bar', 10)->once();

        $this->storage->add('foo', 'bar', 10);
    }

    /**
     * Replace the storage with our one above that overrides the tag flag, and
     * define expectations for tags() method.
     */
    private function emulateTags()
    {
        $this->storage = new TaggedStorage($this->cache);

        $this->cache->shouldReceive('tags')->with('tymon.jwt')->once()->andReturn(\Mockery::self());
    }

    public function testItShouldAddTheItemToTaggedStorageForever()
    {
        $this->emulateTags();
        $this->cache->shouldReceive('forever')->with('foo', 'bar')->once();

        $this->storage->forever('foo', 'bar');
    }

    public function testItShouldGetAnItemFromTaggedStorage()
    {
        $this->emulateTags();
        $this->cache->shouldReceive('get')->with('foo')->once()->andReturn(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->storage->get('foo'));
    }

    public function testItShouldRemoveTheItemFromTaggedStorage()
    {
        $this->emulateTags();
        $this->cache->shouldReceive('forget')->with('foo')->once()->andReturn(true);

        $this->assertTrue($this->storage->destroy('foo'));
    }

    public function testItShouldRemoveAllTaggedItemsFromStorage()
    {
        $this->emulateTags();
        $this->cache->shouldReceive('flush')->withNoArgs()->once();

        $this->storage->flush();
    }
}
