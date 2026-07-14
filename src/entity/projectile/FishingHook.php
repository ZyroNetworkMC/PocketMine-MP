<?php

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;

class FishingHook extends Projectile {
	public static function getNetworkTypeId() : string{ return "minecraft:fishing_hook"; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.25, 0.25);
	}

	protected function getInitialDragMultiplier() : float{
		return 0.05;
	}

	protected function getInitialGravity() : float{
		return 0.04;
	}

	protected function onHit(ProjectileHitEvent $event) : void{
		// Default bobber hit logic
	}
}
