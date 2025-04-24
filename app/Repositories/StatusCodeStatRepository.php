<?php

namespace App\Repositories;

use App\Enums\Utility;
use App\Models\StatusCodeStat;

class StatusCodeStatRepository extends BaseRepository
{
    protected $utility;

    public function __construct(Utility $utility)
    {
        parent::__construct();
        $this->utility = $utility;
    }

    public function model()
    {
        return StatusCodeStat::class;
    }
}
