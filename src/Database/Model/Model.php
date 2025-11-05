<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Model Attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Model {

    public bool $hasUsers;
    public bool $hasTimestamps;
    public bool $canCreate;
    public bool $canEdit;
    public bool $canDelete;
    public bool $usesRequest;



    /**
     * The Model Attribute
     * @param boolean $hasUsers      Optional.
     * @param boolean $hasTimestamps Optional.
     * @param boolean $canCreate     Optional.
     * @param boolean $canEdit       Optional.
     * @param boolean $canDelete     Optional.
     * @param boolean $usesRequest   Optional.
     */
    public function __construct(
        bool $hasUsers      = false,
        bool $hasTimestamps = false,
        bool $canCreate     = false,
        bool $canEdit       = false,
        bool $canDelete     = false,
        bool $usesRequest   = true,
    ) {
        $this->hasUsers      = $hasUsers;
        $this->hasTimestamps = $hasTimestamps;
        $this->canCreate     = $canCreate;
        $this->canEdit       = $canEdit;
        $this->canDelete     = $canDelete;
        $this->usesRequest   = $usesRequest;
    }
}
