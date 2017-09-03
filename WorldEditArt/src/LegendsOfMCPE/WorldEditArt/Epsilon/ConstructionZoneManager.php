<?php

/*
 *
 * WorldEditArt
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace LegendsOfMCPE\WorldEditArt\Epsilon;

use LegendsOfMCPE\WorldEditArt\Epsilon\LibgeomAdapter\ShapeWrapper;
use pocketmine\math\Vector3;
use pocketmine\utils\Binary;
use sofe\libgeom\io\LibgeomDataReader;
use sofe\libgeom\io\LibgeomDataWriter;
use sofe\libgeom\io\LibgeomLittleEndianDataReader;
use sofe\libgeom\io\LibgeomLittleEndianDataWriter;
use sofe\libgeom\UnsupportedOperationException;

class ConstructionZoneManager implements \Serializable{
	/** @var WorldEditArt */
	private $plugin;
	/** @var string */
	private $file;

	/** @var bool */
	private $configCheck;
	/** @var string[] */
	private $configWorlds;

	/** @var ConstructionZone[] */
	private $constructionZones;

	/** @var string|null */
	private $cachedSerialization = null;

	// TODO Reminder: Reset cachedSerialization to null upon modifying constructionZones

	public function __construct(WorldEditArt $plugin){
		$this->plugin = $plugin;
		$this->file = $this->plugin->getDataFolder() . "constructionZones.dat";
		$this->configCheck = $this->plugin->getConfig()->get(Consts::CONFIG_CONSTRUCTION_ZONE_CHECK);
		/** @noinspection UnnecessaryCastingInspection */
		$this->configWorlds = (array) ($this->plugin->getConfig()->get(Consts::CONFIG_CONSTRUCTION_ZONE_WORLDS) ?: []);
		$this->load();
	}

	/**
	 * Returns all active construction zones on the server
	 *
	 * The keys of the array are the names of the construction zones in lowercase. The case-preserved name can be obtained from
	 * {@see ConstructionZone::getName()}
	 *
	 * @return ConstructionZone[]
	 */
	public function getConstructionZones() : array{
		return $this->constructionZones;
	}

	/**
	 * @return bool
	 */
	public function checks() : bool{
		return $this->configCheck;
	}

	/**
	 * @return string[]
	 */
	public function getWorlds() : array{
		return $this->configWorlds;
	}


	public function releaseBySession(BuilderSession $session) : void{
		foreach($this->constructionZones as $zone){
			if($zone->getLockingSession() === spl_object_hash($session->getOwner())){
				$zone->unlock();
			}
		}
	}

	public function calcCczValue(string $levelName) : bool{
		return !($this->configCheck && !in_array($levelName, $this->configWorlds, true));
	}

	public function canDoEdit(Vector3 $vector, string $levelName, string $sessionOwnerHash, bool $cczValue){
		foreach($this->constructionZones as $zone){
			if($zone->getShape()->getLevelName() !== $levelName){
				continue;
			}
			$inState = 0; // cache isInside() result
			if($zone->isLocked() && $sessionOwnerHash !== $zone->getLockingSession()){
				$inState = $zone->getShape()->isInside($vector) ? 2 : 1;
				if($inState !== 2){
					return false;
				}
			}
			if(!$cczValue){
				if($inState === 0){
					$inState = $zone->getShape()->isInside($vector);
				}
				if($inState === 2){
					$cczValue = true;
				}

				if($inState !== 0 && $zone->getShape()->isInside($vector)){
					$cczValue = true;
				}
			}
		}
		return $cczValue;
	}


	public function load() : void{
		if(is_file($this->file)){
			$reader = LibgeomLittleEndianDataReader::fromFile($this->file);
			try{
				$this->read($reader);
			}/** @noinspection BadExceptionsProcessingInspection */catch(\UnderflowException $e){
				$this->plugin->getLogger()->error("Corrupted constructionZones.dat, resetting to empty...");
				file_put_contents($this->file, Binary::writeUnsignedVarInt(0));
				$this->constructionZones = [];
			}finally{
				$reader->close();
			}
		}else{
			$this->constructionZones = [];
		}
	}

	public function save() : void{
		$writer = LibgeomLittleEndianDataWriter::toFile($this->file);
		$this->write($writer);
		$writer->close();
	}


	public function read(LibgeomDataReader $reader) : void{
		$version = $reader->readShort();
		if($version !== 1){
			throw new UnsupportedOperationException("Unsupported constructionZones.dat version ($version, only supports 1)");
		}
		$count = $reader->readVarInt(false);
		$this->constructionZones = [];
		for($i = 0; $i < $count; ++$i){
			$name = $reader->readString();
			/** @var string|\sofe\libgeom\Shape $class */
			$class = $reader->readString();
			$shape = $class::fromBinary($this->plugin->getServer(), $reader);
			$wrappedShape = new ShapeWrapper($shape);
			$this->constructionZones[mb_strtolower($name)] = new ConstructionZone($name, $wrappedShape);
		}
	}

	public function write(LibgeomDataWriter $writer) : void{
		$writer->writeShort(1); // version
		$writer->writeVarInt(count($this->constructionZones), false);
		foreach($this->constructionZones as $zone){
			$shape = $zone->getShape()->getBaseShape();
			$writer->writeString($zone->getName());
			$writer->writeString(get_class($shape));
			$shape->toBinary($writer);
		}
	}

	public function serialize() : string{
		return $this->cachedSerialization ?? ($this->cachedSerialization =
				serialize([$this->configCheck, $this->configWorlds, $this->constructionZones]));
	}

	public function unserialize($serialized) : void{
		$this->cachedSerialization = $serialized;
		[$this->configCheck, $this->configWorlds, $this->constructionZones] = unserialize($serialized, true);
	}
}
