<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

class Composition extends Model
{
    use HasFactory;

    protected $fillable = ['id','name', 'description','tier','difficulty','playing_style','likes', 'type']; 

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function champions()
    {
        return $this->hasMany(Champion::class);
    }

    protected $table = 'compositions';

    public function formation()
    {
        return $this->belongsToMany(Champion::class, 'formation', 'compo_id', 'champion_id')
                    ->withPivot('slot_table')
                    ->withTimestamps();
    }

    public function synergies()
    {
        return $this->belongsToMany(Synergy::class);
    }

    public function tier()
    {
        return $this->belongsTo(Tier::class);
    }

    public function formations()
    {
        return $this->hasMany(Formation::class, 'compo_id');
    }

    public function userCompo()
    {
        return $this->hasOne(UserCompo::class, 'composition_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_compo', 'composition_id', 'user_id');
    }

    public function prioCarrusel()
    {
        return $this->hasMany(PrioCarrusel::class, 'composition_id');
    }

    public function augment()
    {
        return $this->hasMany(Augment::class);
    }
    
    public function augments()
    {
        return $this->hasMany(AugmentComp::class, 'composition_id');
    }

    public function augmentsByTier($tier)
    {
        return $this->augments()->where('tier', $tier)->pluck('id');
    }

    public function compositionLikes()
    {
        return $this->hasMany(CompositionLike::class);
    }


    public function formationItems()
    {
        return $this->hasMany(FormationItem::class, 'compo_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'composition_likes');
    }

    public static function getUserCompositionById($compId, $userId)
    {
        return self::where('id', $compId)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->first();
    }

    public static function createWithFormations(array $compositionData, array $formationsData, array $prioCarruselData, array $augmentsData, int $userId)
    {
        $championCounts = array_count_values(array_column($formationsData, 'champion_id'));
        foreach ($championCounts as $championId => $count) {
            if ($count > 2) {
                throw new Exception("You cannot have more than 2 champions with the ID {$championId}.");
            }
        }
    
        $slots = [];
        foreach ($formationsData as $formation) {
            $slot = $formation['slot_table'];
            if ($slot < 1 || $slot > 28) {
                throw new Exception('The slot_table coordinate must be between 1 and 28.');
            }
            if (in_array($slot, $slots)) {
                throw new Exception('Each champion must be at a different coordinate.');
            }
            $slots[] = $slot;
        }
    
        $validTiers = [1, 2, 3];
        foreach ($augmentsData as $tier => $augmentIds) {
            if (!in_array($tier, $validTiers)) {
                throw new Exception("Invalid tier value {$tier}. Allowed values are 1, 2, and 3.");
            }
    
            if (count($augmentIds) > 3) {
                throw new Exception("You cannot have more than 3 augments for tier {$tier}.");
            }
    
            foreach ($augmentIds as $augmentId) {
                $augment = Augment::find($augmentId);
                if (!$augment) {
                    throw new Exception("Augment with ID {$augmentId} does not exist.");
                }
                if ($augment->tier != $tier) {
                    throw new Exception("Augment with ID {$augmentId} does not match the specified tier {$tier}.");
                }
            }
        }
    
        return DB::transaction(function () use ($compositionData, $formationsData, $prioCarruselData, $augmentsData, $userId) {
            $composition = self::create($compositionData);

            $championItemCounts = [];
            foreach ($formationsData as $index => $formationData) {
                $championId = $formationData['champion_id'];
                $itemIds = $formationData['item_ids'] ?? [];
                $instanceKey = $index;
    
                if (!isset($championItemCounts[$instanceKey])) {
                    $championItemCounts[$instanceKey] = 0;
                }
    
                $currentItemCount = count($itemIds);
                $totalItemCount = $championItemCounts[$instanceKey] + $currentItemCount;
    
                if ($totalItemCount > 3) {
                    throw new Exception("The champion with ID {$championId} in the instance {$instanceKey} cannot have more than 3 items in total.");
                }
    
                $championItemCounts[$instanceKey] = $totalItemCount;
    
                foreach ($itemIds as $itemId) {
                    $formation = Formation::create([
                        'champion_id' => $championId,
                        'slot_table' => $formationData['slot_table'],
                        'compo_id' => $composition->id,
                        'star' => $formationData['star'],
                        'item_id' => $itemId
                    ]);
    
                    $existingEntry = DB::table('formation_item')
                        ->where('formation_id', $formation->id)
                        ->where('item_id', $itemId)
                        ->where('compo_id', $composition->id)
                        ->exists();
    
                    if (!$existingEntry) {
                        DB::table('formation_item')->insert([
                            'formation_id' => $formation->id,
                            'item_id' => $itemId,
                            'compo_id' => $composition->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if (empty($itemIds)) {
                    Formation::create([
                        'champion_id' => $championId,
                        'slot_table' => $formationData['slot_table'],
                        'compo_id' => $composition->id,
                        'star' => $formationData['star'],
                        'item_id' => null
                    ]);
                }
            }

            foreach ($prioCarruselData as $prioCarruselItem) {
                PrioCarrusel::create([
                    'composition_id' => $composition->id,
                    'item_id' => $prioCarruselItem['item_id'],
                ]);
            }

            foreach ($augmentsData as $tier => $augmentIds) {
                foreach ($augmentIds as $augmentId) {
                    AugmentComp::create([
                        'composition_id' => $composition->id,
                        'augment_id' => $augmentId,
                    ]);
                }
            }

            UserCompo::create([
                'user_id' => $userId,
                'composition_id' => $composition->id,
            ]);

            $synergies = self::calculateSynergyActivation($formationsData);

            return [
                'composition' => $composition,
                'synergies' => $synergies
            ];
        });
    }
    public static function updateComposition($id, array $compositionData, array $formationsData, array $prioCarruselData, array $augmentsData)
    {
        return DB::transaction(function () use ($id, $compositionData, $formationsData, $prioCarruselData, $augmentsData) {
            $composition = self::findOrFail($id);
            $composition->update($compositionData);

            Formation::where('compo_id', $composition->id)->delete();
            FormationItem::where('compo_id', $composition->id)->delete();
            PrioCarrusel::where('composition_id', $composition->id)->delete();
            AugmentComp::where('composition_id', $composition->id)->delete();

            $slots = [];
            foreach ($formationsData as $formation) {
                $slot = $formation['slot_table'];
                if (in_array($slot, $slots)) {
                    throw new Exception("The slot_table coordinate {$slot} is duplicated.");
                }
                $slots[] = $slot;
            }

            $championCounts = array_count_values(array_column($formationsData, 'champion_id'));
            foreach ($championCounts as $championId => $count) {
                if ($count > 2) {
                    throw new Exception("You cannot have more than 2 champions with the ID {$championId}.");
                }
            }

            $championItemCounts = [];
            foreach ($formationsData as $index => $formationData) {
                $itemIds = $formationData['item_ids'] ?? [];
                $championId = $formationData['champion_id'];
                $slotTable = $formationData['slot_table'];
                $star = $formationData['star'];
    
                $instanceKey = $index;
                if (!isset($championItemCounts[$instanceKey])) {
                    $championItemCounts[$instanceKey] = 0;
                }
    
                $currentItemCount = count($itemIds);
                $totalItemCount = $championItemCounts[$instanceKey] + $currentItemCount;
    
                if ($totalItemCount > 3) {
                    throw new Exception("The champion with ID {$championId} cannot have more than 3 items in total.");
                }
    
                $championItemCounts[$instanceKey] = $totalItemCount;
    
                foreach ($itemIds as $itemId) {
                    $formation = Formation::updateOrCreate(
                        [
                            'champion_id' => $championId,
                            'slot_table' => $slotTable,
                            'compo_id' => $composition->id,
                            'star' => $star,
                            'item_id' => $itemId
                        ]
                    );
    
                    FormationItem::updateOrCreate(
                        [
                            'formation_id' => $formation->id,
                            'item_id' => $itemId,
                            'compo_id' => $composition->id
                        ]
                    );
                }
    
                if (empty($itemIds)) {
                    $formation = Formation::updateOrCreate(
                        [
                            'champion_id' => $championId,
                            'slot_table' => $slotTable,
                            'compo_id' => $composition->id,
                            'star' => $star,
                            'item_id' => null
                        ]
                    );
    
                    FormationItem::updateOrCreate(
                        [
                            'formation_id' => $formation->id,
                            'item_id' => null,
                            'compo_id' => $composition->id
                        ]
                    );
                }
            }

            $uniquePrioCarruselItems = array_unique(array_column($prioCarruselData, 'item_id'));
            if (count($uniquePrioCarruselItems) != count($prioCarruselData)) {
                throw new Exception("Duplicate items are not allowed in prio_carrusel.");
            }
    
            foreach ($prioCarruselData as $prioCarruselItem) {
                $item = Item::findOrFail($prioCarruselItem['item_id']);
                if ($item->type_object !== 'Basic') {
                    throw new Exception("Only Basic items are allowed in prio_carrusel. Item ID {$item->id} is not a Basic item.");
                }
    
                PrioCarrusel::updateOrCreate(
                    [
                        'composition_id' => $composition->id,
                        'item_id' => $prioCarruselItem['item_id'],
                    ]
                );
            }

            $tierLimits = [
                1 => 3,
                2 => 3,
                3 => 3
            ];
    
            $validAugmentsData = [
                1 => [],
                2 => [],
                3 => []
            ];
    
            foreach ($augmentsData as $tier => $augmentIds) {
                if (!isset($tierLimits[$tier])) {
                    throw new Exception("Invalid tier specified.");
                }
    
                if (count($augmentIds) > $tierLimits[$tier]) {
                    throw new Exception("Cannot have more than {$tierLimits[$tier]} augments for tier {$tier}.");
                }
    
                foreach ($augmentIds as $augmentId) {
                    $augment = Augment::findOrFail($augmentId);
                    if ($augment->tier != $tier) {
                        throw new Exception("Augment with ID {$augmentId} does not match the specified tier {$tier}.");
                    }
                    $validAugmentsData[$tier][] = $augmentId;
                }
            }
    
            foreach ($validAugmentsData as $tier => $augmentIds) {
                foreach ($augmentIds as $augmentId) {
                    AugmentComp::updateOrCreate(
                        [
                            'composition_id' => $composition->id,
                            'augment_id' => $augmentId
                        ]
                    );
                }
            }

            $synergies = self::calculateSynergyActivation($formationsData);

            return [
                'composition' => $composition,
                'synergies' => $synergies,
            ];
        });
    }

    public function deleteComposition()
    {
        DB::beginTransaction();

        try {
            $this->formations()->delete();
            $this->prioCarrusel()->delete();
            $this->augments()->delete();
            $this->userCompo()->delete();
            $this->formationItems()->delete();

            $this->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to delete composition: ' . $e->getMessage());
        }
    }

    public static function getCompositions($tier = null, $synergyName = null, $userId = null, $sortBy = null, $publishedOnly = false, $metaOnly = false)
    {

        $query = self::query();

        if ($publishedOnly) {
            $query->where('type', 'publish');
        }

        if ($metaOnly) {
            $query->where('type', 'meta');
        }

        if ($userId) {
            $query->join('user_compo', 'user_compo.composition_id', '=', 'compositions.id')
                  ->select('compositions.*')
                  ->where('user_compo.user_id', $userId);
        }

        $query->with([
            'formations.champion.synergies',
            'formations.items',
            'augments.augment',
            'users',
        ]);

        if ($tier) {
            $query->where('compositions.tier', $tier);
        }

        if ($synergyName) {
            $query->whereHas('formations.champion.synergies', function ($q) use ($synergyName) {
                $q->where('name', 'like', '%' . $synergyName . '%');
            });
        }

        switch ($sortBy) {
            case 'likes':
                $query->orderBy('likes', 'desc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query->get();
    }

    public function isLikedBy(User $user)
    {
        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }

    public function addLike()
    {
        $this->likes++;
        $this->save();
    }
    
    public function removeLike()
    {
        $this->likes = max(0, $this->likes - 1);
        $this->save();
    }

    public function changeType($type)
    {
        $validTypes = ['private', 'publish'];
        if (!in_array($type, $validTypes)) {
            return 'Invalid type provided.';
        }

        if ($this->userCompo()->where('user_id', Auth::id())->doesntExist()) {
            return 'Unauthorized: You do not own this composition.';
        }

        $this->type = $type;
        return $this->save();
    }

    public static function calculateSynergyActivation(array $formationsData)
    {
        $synergies = [];
        $championIdsCounted = [];
    
        foreach ($formationsData as $formation) {
            $champion = Champion::with(['synergies'])->find($formation['champion_id']);
            
            if (!$champion) {
                continue;
            }
    
            foreach ($champion->synergies as $synergy) {
                if (!isset($synergies[$synergy->id])) {
                    $synergies[$synergy->id] = [
                        'name' => $synergy->name,
                        'type' => $synergy->type,
                        'description' => $synergy->description,
                        'icon_synergy' => $synergy->icon_synergy,
                        'activation' => json_decode($synergy->synergy_activation, true),
                        'champion_count' => 0,
                        'highlighted' => [],
                        'emblem_items' => [],
                        'color' => 'default',
                    ];
                }

                $synergyId = $synergy->id;
                if (!isset($championIdsCounted[$synergyId])) {
                    $championIdsCounted[$synergyId] = [];
                }

                if (!in_array($formation['champion_id'], $championIdsCounted[$synergyId])) {
                    $synergies[$synergyId]['champion_count']++;
                    $championIdsCounted[$synergyId][] = $formation['champion_id'];
                }
            }
    
            foreach ($formation['item_ids'] as $itemId) {
                $item = Item::find($itemId);
                if ($item && str_ends_with($item->name, 'Emblem')) {
                    $synergyName = explode(' ', $item->name)[0];
        
                    $synergyFound = false;
                    foreach ($synergies as $synergyId => $synergyData) {
                        if (strtolower($synergyData['name']) === strtolower($synergyName)) {
                            $synergies[$synergyId]['emblem_items'][] = $item->id;
                            $synergies[$synergyId]['champion_count']++;
                            $synergyFound = true;
                            break;
                        }
                    }
        
                    if (!$synergyFound) {
                        $synergy = Synergy::where('name', $synergyName)->first();
                        if ($synergy) {
                            $synergies[$synergy->id] = [
                                'name' => $synergy->name,
                                'type' => $synergy->type,
                                'description' => $synergy->description,
                                'icon_synergy' => $synergy->icon_synergy,
                                'activation' => json_decode($synergy->synergy_activation, true),
                                'champion_count' => 1,
                                'highlighted' => [],
                                'emblem_items' => [$item->id],
                                'color' => 'default',
                            ];
                        }
                    }
                }
            }
        }
    
        foreach ($synergies as $synergyId => $synergyData) {
            $championCount = $synergyData['champion_count'];
            $lastActiveColor = 'default';
            
            foreach ($synergyData['activation'] as $activationIndex => $activation) {
                foreach ($activation as $requiredChampions => $percentage) {
                    $color = self::getColorForActivation($synergyData['name'], $activationIndex, count($synergyData['activation']));
    
                    $highlighted = [
                        'required' => $requiredChampions,
                        'percentage' => $percentage,
                        'active' => $championCount >= $requiredChampions,
                        'color' => $color,
                        'count' => $championCount
                    ];
    
                    if ($highlighted['active']) {
                        $lastActiveColor = $highlighted['color'];
                    }
    
                    $synergies[$synergyId]['highlighted'][] = $highlighted;
                }
            }
    
            $synergies[$synergyId]['color'] = $lastActiveColor;
        }
    
        return $synergies;
    }
    
    public static function getColorForActivation($synergyName, $activationIndex, $totalActivations) {

        $defaultColors = [
            1 => ['green-yellow'],
            2 => ['brown', 'green-yellow'],
            3 => ['brown', 'grey', 'green-yellow'],
            4 => ['brown', 'grey', 'green-yellow', 'green-yellow'],
        ];

        $specialCases = [
            'Eldritch' => [3 => 'multicolor'],
            'Portal' => [3 => 'multicolor'],
            'Preserver' => [2 => 'grey'],
            'Honeymancy' => [1 => 'green-yellow'],
            'Dragon' => [0 => 'grey'],
        ];

        $colors = $defaultColors[$totalActivations] ?? ['green-yellow'];

        if (isset($specialCases[$synergyName][$activationIndex])) {
            return $specialCases[$synergyName][$activationIndex];
        }

        return $colors[$activationIndex] ?? 'green-yellow';
    }
}

