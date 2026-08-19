<?php

namespace App\Models;

use Database\Factories\TournamentParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['full_name', 'nickname', 'phone', 'category'])]
#[UseFactory(TournamentParticipantFactory::class)]
class TournamentParticipant extends Model
{
    use HasFactory;

    public const CATEGORY_BEGINNER = 'beginner';

    public const CATEGORY_UPPER_BEGINNER = 'upper_beginner';
}
