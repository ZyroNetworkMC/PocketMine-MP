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

namespace pocketmine\item;

class FishingRod extends Durable{

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getMaxDurability() : int{
		return 384;
	}

	public function onClickAir(\pocketmine\player\Player $player, \pocketmine\math\Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		foreach($player->getWorld()->getEntities() as $entity){
			if($entity instanceof \pocketmine\entity\projectile\FishingHook && $entity->getOwningEntity() === $player){
				$entity->flagForDespawn();
				$this->applyDamage(1);
				return ItemUseResult::SUCCESS;
			}
		}

		$location = $player->getLocation();
		$hook = new \pocketmine\entity\projectile\FishingHook(\pocketmine\entity\Location::fromObject(
			$player->getEyePos(),
			$player->getWorld(),
			($location->yaw > 180 ? 360 : 0) - $location->yaw,
			-$location->pitch
		), $player);
		$hook->setMotion($directionVector->multiply(1.5));
		
		$projectileEv = new \pocketmine\event\entity\ProjectileLaunchEvent($hook);
		$projectileEv->call();
		if($projectileEv->isCancelled()){
			$hook->flagForDespawn();
			return ItemUseResult::FAIL;
		}
		
		$hook->spawnToAll();
		return ItemUseResult::SUCCESS;
	}
}
