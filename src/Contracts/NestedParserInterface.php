<?php
namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\RelationInfo;

interface NestedParserInterface
{
    
    /**
     * Returns RelationInfo instance for nested data element by dot notation data key.
     *
     * @param string $key
     * @return RelationInfo|false     false if data could not be determined
     */
    public function getRelationInfoForDataKeyInDotNotation($key);

}
