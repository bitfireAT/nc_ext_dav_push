<?php

namespace OCA\DavPush\Interface;

interface TableSerializable {
	/**
	 * Serializes the object to a dict array to be rendered into a occ command output table
	 * @return array
	 */
	function tableSerialize(?array $params = null);
}