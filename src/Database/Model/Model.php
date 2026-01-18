<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Model Attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Model {

    public string $fantasyName;
    public bool $hasUsers;
    public bool $hasTimestamps;
    public bool $canCreate;
    public bool $canEdit;
    public bool $canDelete;



    /**
     * The Model Attribute
     * @param string $fantasyName   Optional.
     * @param bool   $hasUsers      Optional.
     * @param bool   $hasTimestamps Optional.
     * @param bool   $canCreate     Optional.
     * @param bool   $canEdit       Optional.
     * @param bool   $canDelete     Optional.
     */
    public function __construct(
        string $fantasyName = "",
        bool $hasUsers = false,
        bool $hasTimestamps = false,
        bool $canCreate = false,
        bool $canEdit = false,
        bool $canDelete = false,
    ) {
        $this->fantasyName   = $fantasyName;
        $this->hasUsers      = $hasUsers;
        $this->hasTimestamps = $hasTimestamps;
        $this->canCreate     = $canCreate;
        $this->canEdit       = $canEdit;
        $this->canDelete     = $canDelete;
    }
}
