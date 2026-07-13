<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\world\format;

use pocketmine\world\thread\Future;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\LoadedChunkData;
use pocketmine\world\format\io\WorldData;
use pocketmine\world\format\io\WorldProvider;
use pocketmine\world\format\io\WritableWorldProvider;
use function igbinary_serialize;
use function igbinary_unserialize;

class BaseThreadedWorldProvider implements ThreadedWorldProvider{
	public function __construct(
		private int $minY,
		private int $maxY,
		private string $path,
		private string $world
	){

	}

	public function getWorldMinY() : int{
		return $this->minY;
	}

	public function getWorldMaxY() : int{
		return $this->maxY;
	}

	public function getPath() : string{
		return $this->path;
	}

	public function loadChunk(int $chunkX, int $chunkZ) : Future{
		return WorldProviderThread::getInstance()->transaction($this->world, static function(WorldProvider $provider) use ($chunkZ, $chunkX) : ?LoadedChunkData{
			return $provider->loadChunk($chunkX, $chunkZ);
		}) ?? throw new \RuntimeException("World provider thread is not running");
	}

	public function getWorldData() : Future{
		return WorldProviderThread::getInstance()->transaction($this->world, static function(WorldProvider $provider) : WorldData{
			return $provider->getWorldData();
		}) ?? throw new \RuntimeException("World provider thread is not running");
	}

	public function calculateChunkCount() : Future{
		return WorldProviderThread::getInstance()->transaction($this->world, static function(WorldProvider $provider) : int{
			return $provider->calculateChunkCount();
		}) ?? throw new \RuntimeException("World provider thread is not running");
	}

	/**
	 * Saves a chunk (usually to disk).
	 */
	public function saveChunk(int $chunkX, int $chunkZ, ChunkData $chunkData, int $dirtyFlags) : Future{
		$chunkDataStr = igbinary_serialize($chunkData);
		assert(is_string($chunkDataStr));
		return WorldProviderThread::getInstance()->transaction($this->world, static function(WorldProvider $provider) use ($chunkZ, $chunkX, $chunkDataStr, $dirtyFlags) : void{
			if($provider instanceof WritableWorldProvider){
				/** @var ChunkData $deserialized */
				$deserialized = igbinary_unserialize($chunkDataStr);
				$provider->saveChunk($chunkX, $chunkZ, $deserialized, $dirtyFlags);
			}else{
				throw new \RuntimeException("not saved");
			}
		}) ?? throw new \RuntimeException("World provider thread is not running");
	}

	public function reloadWorldData() : Future{
		return WorldProviderThread::getInstance()->transaction($this->world, static function(WorldProvider $provider) : void{
			$provider->reloadWorldData();
		}) ?? throw new \RuntimeException("World provider thread is not running");
	}

	public function doGarbageCollection() : Future{
		return WorldProviderThread::getInstance()->transaction($this->world, static function(WorldProvider $provider) : void{
			$provider->doGarbageCollection();
		}) ?? throw new \RuntimeException("World provider thread is not running");
	}
}
