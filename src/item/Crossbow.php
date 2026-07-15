<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;

class Crossbow extends Tool implements Releasable{

	private const TAG_CHARGED_ITEM = "chargedItem";

	public function getFuelTime() : int{
		return 200;
	}

	public function getMaxDurability() : int{
		return 326;
	}

	public function getChargedItem() : ?Item{
		$tag = $this->getNamedTag()->getCompoundTag(self::TAG_CHARGED_ITEM);
		if($tag !== null){
			return Item::safeNbtDeserialize($tag, "Crossbow charged item");
		}
		return null;
	}

	public function setChargedItem(Item $item) : self{
		$this->getNamedTag()->setTag(self::TAG_CHARGED_ITEM, $item->nbtSerialize());
		return $this;
	}

	public function clearChargedItem() : self{
		$this->getNamedTag()->removeTag(self::TAG_CHARGED_ITEM);
		return $this;
	}

	public function isCharged() : bool{
		return $this->getNamedTag()->getTag(self::TAG_CHARGED_ITEM) !== null;
	}

	public function canStartUsingItem(Player $player) : bool{
		if($this->isCharged()){
			return false;
		}
		return !$player->hasFiniteResources() || $player->getOffHandInventory()->contains($arrow = VanillaItems::ARROW()) || $player->getInventory()->contains($arrow);
	}

	public function onReleaseUsing(Player $player, array &$returnedItems) : ItemUseResult{
		if($this->isCharged()){
			return ItemUseResult::NONE;
		}

		$diff = $player->getItemUseDuration();
		$quickCharge = 0; // TODO: Implement QUICK_CHARGE enchantment when added to PMMP
		$chargeTime = max(0, 25 - ($quickCharge * 5));
		
		if($diff < $chargeTime){
			return ItemUseResult::FAIL;
		}

		$arrow = VanillaItems::ARROW();
		$inventory = match(true){
			$player->getOffHandInventory()->contains($arrow) => $player->getOffHandInventory(),
			$player->getInventory()->contains($arrow) => $player->getInventory(),
			default => null
		};

		if($player->hasFiniteResources() && $inventory === null){
			return ItemUseResult::FAIL;
		}

		if($player->hasFiniteResources()){
			$inventory?->removeItem($arrow->setCount(1));
		}

		$this->setChargedItem($arrow->setCount(1));

		return ItemUseResult::SUCCESS;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		if(!$this->isCharged()){
			return ItemUseResult::NONE;
		}

		$chargedItem = $this->getChargedItem();
		if($chargedItem === null){
			$this->clearChargedItem();
			return ItemUseResult::FAIL;
		}

		$location = $player->getLocation();
		$baseForce = 3.15;

		$entity = new ArrowEntity(Location::fromObject(
			$player->getEyePos(),
			$player->getWorld(),
			($location->yaw > 180 ? 360 : 0) - $location->yaw,
			-$location->pitch
		), $player, true);
		
		$entity->setMotion($directionVector);

		$ev = new EntityShootBowEvent($player, $this, $entity, $baseForce);
		$ev->call();

		$entity = $ev->getProjectile(); 

		if($ev->isCancelled()){
			$entity->flagForDespawn();
			return ItemUseResult::FAIL;
		}

		$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));

		if($entity instanceof Projectile){
			$projectileEv = new ProjectileLaunchEvent($entity);
			$projectileEv->call();
			if($projectileEv->isCancelled()){
				$ev->getProjectile()->flagForDespawn();
				return ItemUseResult::FAIL;
			}
			$ev->getProjectile()->spawnToAll();
			$location->getWorld()->addSound($location, new BowShootSound());
		}else{
			$entity->spawnToAll();
		}

		$this->applyDamage(1);
		$this->clearChargedItem();

		return ItemUseResult::SUCCESS;
	}
}
