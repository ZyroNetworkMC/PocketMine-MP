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

use PHPUnit\Framework\TestCase;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\world\format\io\FastChunkSerializer;

class ChunkTest extends TestCase{

	public function testClone() : void{
		$chunk = new Chunk([], false);
		$chunk->setBlockStateId(0, 0, 0, 1);
		$chunk->setBiomeId(0, 0, 0, 1);
		$chunk->setHeightMap(0, 0, 1);

		$chunk2 = clone $chunk;
		$chunk2->setBlockStateId(0, 0, 0, 2);
		$chunk2->setBiomeId(0, 0, 0, 2);
		$chunk2->setHeightMap(0, 0, 2);

		self::assertNotSame($chunk->getBlockStateId(0, 0, 0), $chunk2->getBlockStateId(0, 0, 0));
		self::assertNotSame($chunk->getBiomeId(0, 0, 0), $chunk2->getBiomeId(0, 0, 0));
		self::assertNotSame($chunk->getHeightMap(0, 0), $chunk2->getHeightMap(0, 0));
	}

	public function testSerializeSubChunkCount() : void{
		$chunk = new Chunk([], false);
		$translator = TypeConverter::getInstance()->getBlockTranslator();
		$expected = ChunkSerializer::serializeFullChunk($chunk, DimensionIds::OVERWORLD, $translator);
		$actual = ChunkSerializer::serializeFullChunk($chunk, DimensionIds::OVERWORLD, $translator, null, ChunkSerializer::getSubChunkCount($chunk, DimensionIds::OVERWORLD));

		self::assertSame($expected, $actual);
	}

	public function testUsage(): void {
		$chunk = new Chunk([], false);
		$translator = TypeConverter::getInstance()->getBlockTranslator();

		for($y = 0; $y < 16; $y++){
			for($x = 0; $x < 16; $x++){
				for($z = 0; $z < 16; $z++){
					$blockId = ($y < 4 ? BlockTypeIds::STONE : ($y < 6 ? BlockTypeIds::DIRT : BlockTypeIds::GRASS));
					$chunk->setBlockStateId($x, $y, $z, $blockId);
					$chunk->setBiomeId($x, $y, $z, BiomeIds::PLAINS);
				}
			}
		}

		for($x = 0; $x < 16; $x++){
			for($z = 0; $z < 16; $z++){
				$chunk->setHeightMap($x, $z, 6);
			}
		}
		
		$chunk->setPopulated(true);

		gc_collect_cycles();
		$startTime = hrtime(true);
		$startMemory = memory_get_usage(true);

		for($i = 0; $i < 1000; $i++){
			$serialized = ChunkSerializer::serializeFullChunk($chunk, DimensionIds::OVERWORLD, $translator);
			$deserialized = FastChunkSerializer::deserializeTerrain(FastChunkSerializer::serializeTerrain($chunk));
			self::assertNotEmpty($serialized);
		}

		gc_collect_cycles();
		$endTime = hrtime(true);
		$endMemory = memory_get_usage(true);
		$peakMemory = memory_get_peak_usage(true);
		$elapsedMs = ($endTime - $startTime) / 1_000_000;
		$memoryDelta = $endMemory - $startMemory;

		fwrite(STDOUT, "Chunk serialization/deserialization 1000x (vanilla-like): {$elapsedMs} ms, memory delta: {$memoryDelta} bytes, peak memory: {$peakMemory} bytes\n");
	}
}
