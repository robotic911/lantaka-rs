@extends('layouts.client')

<title>Checkout - Lantaka Reservation System</title>
<link rel="stylesheet" href="{{ asset('css/client_my_bookings.css') }}">
<link
    href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet">
@vite('resources/js/my_booking.js')

@section('content')
    <div>
        <h1 class="page-title">Checkout</h1>
    </div>

    @if(session('error'))
        <div class="checkout-flash checkout-flash--error">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="checkout-flash checkout-flash--success">{{ session('success') }}</div>
    @endif

    <div class="checkout-container">
        {{-- ─── Left: Cart items ─────────────────────────────────────── --}}
        <section class="cart-items">
            @forelse($processedItems as $item)
                @php
                    $purpose     = strtolower($item['purpose'] ?? '');
                    $isSpiritual = in_array($purpose, ['retreat', 'recollection']);
                    $setMap      = collect($item['selected_sets'] ?? [])->keyBy('Food_Set_ID');
                    $foodMap     = collect($item['selected_foods'] ?? [])->keyBy('Food_ID');
                    $foodSetSel  = $item['food_set_selection'] ?? [];
                    $foodSel     = $item['food_selections']    ?? [];
                    $mealMode    = $item['meal_mode']          ?? [];
                    $foodEnabled = $item['food_enabled']       ?? [];
                    $mealEnabled = $item['meal_enabled']       ?? [];
                    $mealLabels  = [
                        'breakfast' => 'Breakfast', 'lunch' => 'Lunch',
                        'dinner'    => 'Dinner',    'am_snack' => 'AM Snack',
                        'pm_snack'  => 'PM Snack',  'snacks'  => 'Snacks',
                    ];
                @endphp

                <div class="cart-item"
                     data-name="{{ $item['name'] }}"
                     data-total="{{ $item['base_total'] }}"
                     data-base="{{ $item['price'] }}"
                     data-id="{{ $item['id'] }}"
                     data-type="{{ $item['type'] }}"
                     data-in="{{ $item['check_in_raw'] }}"
                     data-out="{{ $item['check_out_raw'] }}"
                     data-pax="{{ $item['pax'] }}"
                     data-days="{{ $item['days'] }}"
                     data-purpose="{{ $purpose }}"
                     data-notes="{{ e(str_replace(["\r\n", "\r", "\n"], ' ', $item['notes'] ?? '')) }}"
                     data-food='@json($item['selected_foods'] ?? [])'
                     data-food-sets='@json($item['selected_sets'] ?? [])'
                     data-food-enabled='@json($foodEnabled)'
                     data-meal-enabled='@json($mealEnabled)'
                     data-food-selections='@json($foodSel)'
                     data-food-set-selection='@json($foodSetSel)'
                     data-meal-mode='@json($mealMode)'
                     data-food-total="{{ $item['food_total'] }}"
                     style="cursor: pointer;">

                    {{-- Image --}}
                    <div class="item-image">
                        <img src="{{ $item['img'] ? media_url($item['img']) : asset('images/' . ($item['type'] === 'room' ? 'placeholder_room' : 'placeholder_venue') . '.svg') }}"
                             alt="{{ $item['name'] }}">
                    </div>

                    {{-- Details --}}
                    <div class="item-details">
                        <div class="item-header">
                            <h3 class="item-name">{{ $item['name'] }}</h3>
                            <p class="item-price">
                                ₱{{ number_format($item['price'], 2) }}
                                <span class="item-price-unit">/{{ $item['type'] === 'room' ? 'night' : 'day' }}</span>
                            </p>
                        </div>

                        <p class="item-type">{{ ucfirst($item['type']) }}</p>
                        <p class="item-guests">👥 {{ $item['pax'] }} Guests</p>

                        <p class="item-dates">
                            {{ $item['check_in'] }}
                            @if($item['check_in'] !== $item['check_out'])
                                → {{ $item['check_out'] }}
                            @endif
                            <span class="item-dates-nights">
                                @if($item['type'] === 'room')
                                    · {{ $item['days'] }} Night{{ $item['days'] > 1 ? 's' : '' }}
                                @else
                                    · {{ $item['days'] }} Day{{ $item['days'] > 1 ? 's' : '' }}
                                @endif
                            </span>
                        </p>

                        @if($item['purpose'])
                            <p class="item-purpose">
                                Purpose: <strong>{{ ucfirst($item['purpose']) }}</strong>
                            </p>
                        @endif

                        {{-- ── Food reservation breakdown ── --}}
                        @if($item['type'] === 'venue')
                            @php
                                // Detect if any date uses individual mode
                                $hasFood = false;
                                foreach ($foodSel as $d => $dm) {
                                    if (($foodEnabled[$d] ?? '1') == '1' && !empty($dm)) { $hasFood = true; break; }
                                }
                                if (!$hasFood) {
                                    foreach ($foodSetSel as $d => $dm) {
                                        if (($foodEnabled[$d] ?? '1') == '1' && !empty($dm)) { $hasFood = true; break; }
                                    }
                                }
                            @endphp

                            @if($hasFood)
                            <div class="item-food-section">
                                <div class="item-food-section-title">🍽 Food Reservation</div>

                                @php
                                    // Collect all dates that need food display
                                    $allDates = array_unique(array_merge(
                                        array_keys($foodSetSel),
                                        array_keys($foodSel)
                                    ));
                                @endphp

                                @foreach($allDates as $date)
                                    @if(($foodEnabled[$date] ?? '1') != '1') @continue @endif

                                    @php
                                        // Is this date in individual / buffet mode?
                                        $dateMealModes = $mealMode[$date] ?? [];
                                        $dateIsIndiv   = !empty($dateMealModes) && in_array('individual', array_values($dateMealModes));
                                        $dateIsBuffet  = !empty($dateMealModes) && in_array('buffet',     array_values($dateMealModes));
                                        $dateSets      = $foodSetSel[$date]    ?? [];
                                        $dateFoodSel   = $foodSel[$date]       ?? [];
                                        $pax           = $item['pax']          ?? 1;

                                        // Per-date food subtotal
                                        $dateSubtotal = 0;

                                        // Sum individual food prices (rice, viand1, viand2, drink, extra_viands, desserts)
                                        if ($dateIsIndiv) {
                                            foreach (['breakfast', 'lunch', 'dinner'] as $iMealKey) {
                                                if (($mealEnabled[$date][$iMealKey] ?? '1') != '1') continue;
                                                $imc = $dateFoodSel[$iMealKey] ?? [];
                                                foreach (['rice', 'viand1', 'viand2', 'drink'] as $fKey) {
                                                    $fid = $imc[$fKey] ?? '';
                                                    if (!empty($fid) && is_numeric($fid)) {
                                                        $f = $foodMap->get((int)$fid);
                                                        if ($f) $dateSubtotal += ($f->Food_Price ?? 0) * $pax;
                                                    }
                                                }
                                                foreach (['extra_viands', 'desserts'] as $arrKey) {
                                                    foreach ((array)($imc[$arrKey] ?? []) as $fid) {
                                                        if (!empty($fid) && is_numeric($fid)) {
                                                            $f = $foodMap->get((int)$fid);
                                                            if ($f) $dateSubtotal += ($f->Food_Price ?? 0) * $pax;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // Sum set prices
                                        foreach ($dateSets as $mealKey => $setIdOrIds) {
                                            $sids = is_array($setIdOrIds) ? $setIdOrIds : [$setIdOrIds];
                                            foreach ($sids as $sid) {
                                                if (empty($sid)) continue;
                                                $dSet = $setMap->get((int)$sid);
                                                if ($dSet) $dateSubtotal += ($dSet->Food_Set_Price ?? 0) * $pax;
                                            }
                                        }
                                        // Sum snack prices (PHP mixed-key: numeric keys hold food IDs, 'snacks' key is placeholder)
                                        $snackKeys = $isSpiritual ? ['am_snack', 'pm_snack'] : ['snacks', 'am_snack', 'pm_snack'];
                                        foreach ($snackKeys as $sk) {
                                            $snackData = $dateFoodSel[$sk] ?? [];
                                            if (!is_array($snackData)) continue;
                                            foreach ($snackData as $k => $sid) {
                                                if (empty($sid) || !is_numeric($sid)) continue;
                                                $sf = $foodMap->get((int)$sid);
                                                if ($sf) $dateSubtotal += ($sf->Food_Price ?? 0) * $pax;
                                            }
                                        }

                                        // Sum buffet meal prices (tier × pax per enabled buffet meal)
                                        if ($dateIsBuffet) {
                                            foreach ($dateMealModes as $bMealKey => $bMode) {
                                                if ($bMode !== 'buffet') continue;
                                                if (($mealEnabled[$date][$bMealKey] ?? '1') != '1') continue;
                                                $tier = (int) ($dateFoodSel[$bMealKey]['_tier'] ?? 350);
                                                $dateSubtotal += $tier * $pax;
                                            }
                                        }
                                    @endphp

                                    <div class="food-date-group">
                                        <div class="food-date-label">
                                            📅 {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
                                        </div>

                                        @if($dateIsIndiv)
                                            {{-- Individual order mode --}}
                                            @foreach(['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'] as $mealKey => $mealLabel)
                                                @if(($mealEnabled[$date][$mealKey] ?? '1') != '1') @continue @endif
                                                @php
                                                    $mc        = $dateFoodSel[$mealKey] ?? [];
                                                    $riceFood  = isset($mc['rice'])   && !empty($mc['rice'])   ? $foodMap->get((int)$mc['rice'])   : null;
                                                    $viand1    = isset($mc['viand1']) && !empty($mc['viand1']) ? $foodMap->get((int)$mc['viand1']) : null;
                                                    $viand2    = isset($mc['viand2']) && !empty($mc['viand2']) ? $foodMap->get((int)$mc['viand2']) : null;
                                                    $drinkFood = isset($mc['drink'])  && !empty($mc['drink'])  ? $foodMap->get((int)$mc['drink'])  : null;
                                                @endphp
                                                @php
                                                    // Per-meal subtotal
                                                    $mealSubtotal = 0;
                                                    foreach ([$riceFood, $viand1, $viand2, $drinkFood] as $_f) {
                                                        if ($_f) $mealSubtotal += ($_f->Food_Price ?? 0) * $pax;
                                                    }
                                                    foreach ((array)($mc['extra_viands'] ?? []) as $_eid) {
                                                        $_ef = $foodMap->get((int)$_eid);
                                                        if ($_ef) $mealSubtotal += ($_ef->Food_Price ?? 0) * $pax;
                                                    }
                                                    foreach ((array)($mc['desserts'] ?? []) as $_did) {
                                                        $_df = $foodMap->get((int)$_did);
                                                        if ($_df) $mealSubtotal += ($_df->Food_Price ?? 0) * $pax;
                                                    }
                                                @endphp
                                                @if($riceFood || $viand1 || $viand2 || $drinkFood)
                                                <div class="food-indiv-meal-group">
                                                    <div class="food-indiv-meal-label">{{ $mealLabel }}</div>
                                                    @if($riceFood)
                                                        <div class="food-indiv-item">
                                                            <span class="food-indiv-cat">Rice</span>
                                                            <span class="food-indiv-name">{{ $riceFood->Food_Name }}</span>
                                                            <span class="food-indiv-price">₱{{ number_format($riceFood->Food_Price, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($viand1)
                                                        <div class="food-indiv-item">
                                                            <span class="food-indiv-cat">Viand</span>
                                                            <span class="food-indiv-name">{{ $viand1->Food_Name }}</span>
                                                            <span class="food-indiv-price">₱{{ number_format($viand1->Food_Price, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($viand2)
                                                        <div class="food-indiv-item">
                                                            <span class="food-indiv-cat">Viand</span>
                                                            <span class="food-indiv-name">{{ $viand2->Food_Name }}</span>
                                                            <span class="food-indiv-price">₱{{ number_format($viand2->Food_Price, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($drinkFood)
                                                        <div class="food-indiv-item">
                                                            <span class="food-indiv-cat">Drink</span>
                                                            <span class="food-indiv-name">{{ $drinkFood->Food_Name }}</span>
                                                            <span class="food-indiv-price">₱{{ number_format($drinkFood->Food_Price, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if(!empty($mc['extra_viands']))
                                                        @foreach((array)$mc['extra_viands'] as $evId)
                                                            @php $evFood = $foodMap->get((int)$evId); @endphp
                                                            @if($evFood)
                                                                <div class="food-indiv-item food-indiv-item--extra">
                                                                    <span class="food-indiv-cat">+ Viand</span>
                                                                    <span class="food-indiv-name">{{ $evFood->Food_Name }}</span>
                                                                    <span class="food-indiv-price">₱{{ number_format($evFood->Food_Price, 2) }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                    @if(!empty($mc['desserts']))
                                                        @foreach((array)$mc['desserts'] as $dId)
                                                            @php $dFood = $foodMap->get((int)$dId); @endphp
                                                            @if($dFood)
                                                                <div class="food-indiv-item food-indiv-item--extra">
                                                                    <span class="food-indiv-cat">Dessert</span>
                                                                    <span class="food-indiv-name">{{ $dFood->Food_Name }}</span>
                                                                    <span class="food-indiv-price">₱{{ number_format($dFood->Food_Price, 2) }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                    @if($mealSubtotal > 0)
                                                        <div class="food-meal-subtotal-row">
                                                            <span>{{ $mealLabel }} Subtotal</span>
                                                            <span>₱{{ number_format($mealSubtotal, 2) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                @endif
                                            @endforeach

                                            {{-- Individual snacks (snacks, am_snack, pm_snack) --}}
                                            @php
                                                $indivSnackKeys = [
                                                    'snacks'   => 'Snack',
                                                    'am_snack' => 'AM Snack',
                                                    'pm_snack' => 'PM Snack',
                                                ];
                                            @endphp
                                            @foreach($indivSnackKeys as $snackKey => $snackLabel)
                                                @if(!empty($dateFoodSel[$snackKey]))
                                                    @php
                                                        $rawSnack = $dateFoodSel[$snackKey];
                                                        $snackList = is_array($rawSnack) ? array_values(array_filter($rawSnack, fn($v) => !empty($v) && $v !== 'snacks')) : array_filter([$rawSnack]);
                                                    @endphp
                                                    @foreach($snackList as $snackId)
                                                        @php $sf = $foodMap->get((int)$snackId); @endphp
                                                        @if($sf)
                                                            <div class="food-meal-row food-meal-row--snack">
                                                                    <span class="food-meal-label">{{ $snackLabel }}</span>
                                                                    <span class="food-set-name">{{ $sf->Food_Name }}</span>
                                                                <span class="food-set-price">₱{{ number_format($sf->Food_Price, 2) }}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            @endforeach

                                        @elseif($isSpiritual)
                                            {{-- Spiritual: set per meal + AM/PM snacks --}}
                                            <div class="food-sets-group">
                                                @foreach(['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'] as $mealKey => $mealLabel)
                                                    @if(isset($dateSets[$mealKey]) && !empty($dateSets[$mealKey]))
                                                        @php
                                                            $set           = $setMap->get((int) $dateSets[$mealKey]);
                                                            $spirMealSel   = $dateFoodSel[$mealKey] ?? [];
                                                            $spirRiceFood  = !empty($spirMealSel['rice_type'])
                                                                ? $foodMap->get((int) $spirMealSel['rice_type'])
                                                                : null;
                                                            $spirDrinkKey  = $mealKey === 'breakfast' ? 'hot_drink' : 'softdrinks';
                                                            $spirDrinkFood = !empty($spirMealSel[$spirDrinkKey])
                                                                ? $foodMap->get((int) $spirMealSel[$spirDrinkKey])
                                                                : null;

                                                            $spirFruitFood = !empty($spirMealSel['fruits'])
                                                                ? $foodMap->get((int) $spirMealSel['fruits'])
                                                                : null;


                                                            // Foods in the set definition (excluding rice/drinks — user's choices fill those slots)
                                                            $spirSetFoods = $set
                                                                ? \App\Models\Food::whereIn('Food_ID', $set->Food_Set_Food_IDs ?? [])
                                                                    ->whereNotIn('Food_Category', ['rice', 'drinks'])
                                                                    ->pluck('Food_Name')->toArray()
                                                                : [];
                                                            $spirDetailParts = array_values(array_filter(array_merge(
                                                                $spirSetFoods,
                                                                $spirRiceFood  ? [$spirRiceFood->Food_Name]  : [],
                                                                $spirDrinkFood ? [$spirDrinkFood->Food_Name] : [],
                                                                $spirFruitFood ? [$spirFruitFood->Food_Name] : []
                                                            )));
                                                        @endphp
                                                        @if($set)
                                                            <div class="food-meal-row">
                                                                <span class="food-meal-label">{{ $mealLabel }}</span>
                                                                <span class="food-set-name" style = "flex: 1;">
                                                                    {{ $set->Food_Set_Name }}
                                                                    @if(count($spirDetailParts) > 0)
                                                                        <span class="food-set-extras">({{ implode(', ', $spirDetailParts) }})</span>
                                                                    @endif
                                                                </span>
                                                                <span class="food-set-price">₱{{ number_format($set->Food_Set_Price, 2) }}</span>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>

                                            @php $hasSnacks = !empty($dateFoodSel['am_snack']) || !empty($dateFoodSel['pm_snack']); @endphp
                                            @if($hasSnacks)
                                                <div class="food-snacks-group">
                                                    <div class="food-snacks-label">Snacks</div>
                                                    @foreach(['am_snack' => 'AM', 'pm_snack' => 'PM'] as $snackKey => $snackLabel)
                                                        @if(!empty($dateFoodSel[$snackKey]))
                                                            @php $snackList = is_array($dateFoodSel[$snackKey]) ? $dateFoodSel[$snackKey] : [$dateFoodSel[$snackKey]]; @endphp
                                                            @foreach($snackList as $snackId)
                                                                @php $sf = $foodMap->get((int)$snackId); @endphp
                                                                @if($sf)
                                                                    <div class="food-meal-row food-meal-row--snack">
                                                                        <span class="food-meal-label">{{ $snackLabel }}</span>
                                                                        <span class="food-set-name" style="flex:1;">{{ $sf->Food_Name }}</span>
                                                                        <span class="food-set-price">₱{{ number_format($sf->Food_Price, 2) }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                        @elseif($dateIsBuffet)
                                            {{-- Buffet mode: flat-rate per pax per meal + individual food items --}}
                                            @php
                                                $buffetFoodKeyMap = [
                                                    'meatviand1'  => 'Meat Viand',
                                                    'meatviand2'  => 'Meat Viand',
                                                    'meatviand3'  => 'Meat Viand',
                                                    'meatviand4'  => 'Meat Viand',
                                                    'noodleviand' => 'Noodle Viand',
                                                    'veggieviand' => 'Veggie Viand',
                                                    'dessert'     => 'Dessert',
                                                ];
                                            @endphp
                                            <div class="food-sets-group">
                                                @foreach(['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner', 'am_snack' => 'AM Snack', 'pm_snack' => 'PM Snack'] as $bMealKey => $bMealLabel)
                                                    @if(($dateMealModes[$bMealKey] ?? '') === 'buffet' && ($mealEnabled[$date][$bMealKey] ?? '1') == '1')
                                                        @php
                                                            $bTier     = (int) ($dateFoodSel[$bMealKey]['_tier'] ?? 350);
                                                            $bMealData = $dateFoodSel[$bMealKey] ?? [];
                                                            $bFoodItems = [];
                                                            foreach ($buffetFoodKeyMap as $bFKey => $bFCat) {
                                                                if (!empty($bMealData[$bFKey]) && is_numeric($bMealData[$bFKey])) {
                                                                    $bFood = $foodMap->get((int) $bMealData[$bFKey]);
                                                                    if ($bFood) $bFoodItems[] = ['cat' => $bFCat, 'food' => $bFood];
                                                                }
                                                            }
                                                        @endphp
                                                        <div class="food-meal-row">
                                                            <span class="food-meal-label">{{ $bMealLabel }}</span>
                                                            <span class="food-set-name">Buffet</span>
                                                            <div class="food-i-n-container">
                                                                @foreach($bFoodItems as $bfi)
                                                                    <span class="food-indiv-name">{{ $bfi['food']->Food_Name }}</span>
                                                                @endforeach
                                                            </div>
                                                            <span class="food-set-price">&#8369;{{ number_format($bTier, 2) }}/pax</span>
                                                        </div>
                                                        
                                                    @endif
                                                @endforeach
                                            </div>

                                        @else
                                            {{-- General set mode --}}
                                            @php
                                                // Collect unique set IDs across all meal keys
                                                $shownSetIds = [];
                                            @endphp
                                            <div class="food-sets-group">
                                                @foreach($dateSets as $mealKey => $setIdOrIds)
                                                    @php $setIdArr = is_array($setIdOrIds) ? $setIdOrIds : [$setIdOrIds]; @endphp
                                                    @foreach($setIdArr as $setId)
                                                        @if(!empty($setId) && !in_array($setId, $shownSetIds))
                                                            @php
                                                                $shownSetIds[]  = $setId;
                                                                $set            = $setMap->get((int)$setId);
                                                                $genMealSel     = $dateFoodSel["gen_{$setId}"] ?? [];
                                                                $genRiceFood    = !empty($genMealSel['rice'])
                                                                    ? $foodMap->get((int) $genMealSel['rice'])
                                                                    : null;
                                                                $genDrinkChoice = $genMealSel['drink_choice'] ?? null;
                                                                // Foods in the set definition (excluding rice/drinks — user choices fill those)
                                                                $genSetFoods = $set
                                                                    ? \App\Models\Food::whereIn('Food_ID', $set->Food_Set_Food_IDs ?? [])
                                                                        ->whereNotIn('Food_Category', ['rice', 'drinks'])
                                                                        ->pluck('Food_Name')->toArray()
                                                                    : [];
                                                                $genDetailParts = array_values(array_filter(array_merge(
                                                                    $genSetFoods,
                                                                    $genRiceFood    ? [$genRiceFood->Food_Name]   : [],
                                                                    $genDrinkChoice ? [ucfirst($genDrinkChoice)]  : []
                                                                )));
                                                            @endphp
                                                            @if($set)
                                                                <div class="food-meal-row">
                                                                    <span class="food-set-name">
                                                                        {{ $set->Food_Set_Name }}
                                                                        @if(count($genDetailParts) > 0)
                                                                            <span class="food-set-extras">({{ implode(', ', $genDetailParts) }})</span>
                                                                        @endif
                                                                    </span>
                                                                    <span class="food-set-price">₱{{ number_format($set->Food_Set_Price, 2) }}/pax</span>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                @endforeach
                                            </div>

                                            {{-- General snacks (snacks, am_snack, pm_snack) --}}
                                            @php
                                                $genSnackKeys = ['snacks' => 'Snack', 'am_snack' => 'AM Snack', 'pm_snack' => 'PM Snack'];
                                                $hasGenSnacks = !empty($dateFoodSel['snacks']) || !empty($dateFoodSel['am_snack']) || !empty($dateFoodSel['pm_snack']);
                                            @endphp
                                            @if($hasGenSnacks)
                                                <div class="food-snacks-group">
                                                    <div class="food-snacks-label">Snacks</div>
                                                    @foreach($genSnackKeys as $snackKey => $snackLabel)
                                                        @if(!empty($dateFoodSel[$snackKey]))
                                                            @php
                                                                $rawSnack  = $dateFoodSel[$snackKey];
                                                                $snackList = is_array($rawSnack) ? array_values(array_filter($rawSnack, fn($v) => !empty($v) && $v !== 'snacks')) : [$rawSnack];
                                                            @endphp
                                                            @foreach($snackList as $snackId)
                                                                @php $sf = $foodMap->get((int)$snackId); @endphp
                                                                @if($sf)
                                                                    <div class="food-meal-row food-meal-row--snack">
                                                                        <span class="food-meal-label">{{ $snackLabel }}</span>
                                                                        <span class="food-set-name" style="flex:1;">{{ $sf->Food_Name }}</span>
                                                                        <span class="food-set-price">₱{{ number_format($sf->Food_Price, 2) }}/pax</span>
                                                                    </div>     
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Per-date subtotal --}}
                                        @if($dateSubtotal > 0)
                                            <div class="food-subtotal-row">
                                                <span>Subtotal ({{ $pax }} pax)</span>
                                                <span>₱{{ number_format($dateSubtotal, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        @endif

                        {{-- Remove / Edit actions --}}
                        <div class="cart-item-actions">
                            <form action="{{ route('checkout.remove') }}" method="POST" class="cart-action-form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $item['key'] }}">
                                <button type="submit" class="cart-action-btn cart-remove-btn"
                                        onclick="return confirm('Remove this item from your cart?')">🗑 Remove</button>
                            </form>
                            <form action="{{ route('checkout.edit') }}" method="POST" class="cart-action-form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $item['key'] }}">
                                <button type="submit" class="cart-action-btn cart-edit-btn">✏️ Edit</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="cart-empty-state">
                    <div class="cart-empty-icon">🛒</div>
                    <p class="cart-empty-text">No items in your cart yet.</p>
                    <a href="{{ route('client.room_venue') }}" class="cart-browse-btn">Browse Rooms & Venues</a>
                </div>
            @endforelse
        </section>

        {{-- ─── Right: Checkout summary ──────────────────────────────── --}}
        <aside class="checkout-summary">
            <h2 class="summary-title">Checkout Summary</h2>

            <div id="empty-msg" class="summary-empty">
                <div class="summary-empty-icon">←</div>
                <p>Click on an item to add it to your reservation.</p>
            </div>

            <div id="summary-details" style="display: none;">
                <form id="confirm-reservation-form" action="{{ route('reservation.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="selected_items" id="selected-items-input">

                    <div class="summary-items" id="summary-items"></div>
                    <div id="summary-foods"></div>

                    <div class="summary-divider"></div>
                    <div class="total-section">
                        <span class="total-label">Total Payable</span>
                        <span class="total-amount" id="summary-grand-total"></span>
                    </div>
                    <button type="submit" class="confirm-btn">CONFIRM RESERVATION</button>
                </form>
            </div>
        </aside>
    </div>
@endsection
