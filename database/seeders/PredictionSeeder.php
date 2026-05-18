<?php

namespace Database\Seeders;

use App\Models\Prediction;
use Illuminate\Database\Seeder;

class PredictionSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = 1;

        $predictions = [
            // Sports
            [
                'category_id' => 1,
                'title' => 'Man City vs Arsenal',
                'scheduled_at' => now()->addDays(1)->setTime(15, 0),
                'confidence_level' => 85,
                'signal' => 'home_win',
                'reason' => 'Man City have won 8 of their last 10 home games. Arsenal missing key midfielder due to injury.',
                'detailed_summary' => 'Manchester City enter this fixture in dominant home form, losing just once at the Etihad this season. Arsenal\'s injury to their key playmaker severely limits their attacking options. City\'s pressing game and depth in midfield should be the difference.',
                'status' => 'active',
                'win_rate' => null,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 1,
                'title' => 'Real Madrid vs Barcelona',
                'scheduled_at' => now()->subDays(3)->setTime(20, 0),
                'confidence_level' => 72,
                'signal' => 'over',
                'reason' => 'El Clasico historically produces high-scoring matches. Both teams averaging 2.8 goals per game.',
                'detailed_summary' => 'The last 5 El Clasico encounters have produced over 2.5 goals. Both squads are in peak attacking form and neither defense has been particularly solid away from home this term.',
                'status' => 'win',
                'win_rate' => 72.50,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 1,
                'title' => 'PSG vs Bayern Munich',
                'scheduled_at' => now()->subDays(7)->setTime(21, 0),
                'confidence_level' => 65,
                'signal' => 'away_win',
                'reason' => 'Bayern\'s superior squad depth and Champions League experience gives them the edge.',
                'detailed_summary' => 'Bayern Munich have an impressive record in knockout European fixtures. PSG tend to underperform in high-pressure legs at home. Bayern\'s defensive structure away from the Allianz has been solid all season.',
                'status' => 'loss',
                'win_rate' => 65.00,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 1,
                'title' => 'Liverpool vs Chelsea',
                'scheduled_at' => now()->addDays(3)->setTime(17, 30),
                'confidence_level' => 78,
                'signal' => 'home_win',
                'reason' => 'Liverpool are unbeaten at Anfield this season. Chelsea struggling with consistency.',
                'detailed_summary' => 'Anfield continues to be a fortress for Liverpool, who have dropped just 2 points at home all season. Chelsea\'s form has been erratic with 3 losses in their last 5 away games. Liverpool\'s front three are in excellent scoring form.',
                'status' => 'active',
                'win_rate' => null,
                'created_by' => $adminId,
            ],

            // Casino
            [
                'category_id' => 2,
                'title' => 'Blackjack — High Stakes Table',
                'scheduled_at' => now()->addHours(6),
                'confidence_level' => 68,
                'signal' => 'over',
                'reason' => 'Card count trending positive. Dealer bust probability elevated at current deck penetration.',
                'detailed_summary' => 'Current shoe analysis shows a high card-rich remaining deck. Basic strategy combined with current count gives a statistically favorable edge. Recommended bet sizing should stay within 1-3% of bankroll.',
                'status' => 'active',
                'win_rate' => null,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 2,
                'title' => 'Roulette — European Wheel',
                'scheduled_at' => now()->subDays(2)->setTime(22, 0),
                'confidence_level' => 60,
                'signal' => 'home_win',
                'reason' => 'Pattern analysis on recent spins shows red/black imbalance over last 50 rounds.',
                'detailed_summary' => 'Statistical deviation observed across the last session. While roulette remains random, the observed imbalance creates a short-window play opportunity. Flat betting strategy recommended to minimise variance.',
                'status' => 'win',
                'win_rate' => 60.00,
                'created_by' => $adminId,
            ],

            // Stocks
            [
                'category_id' => 3,
                'title' => 'AAPL — Apple Inc.',
                'scheduled_at' => now()->addDays(2)->setTime(9, 30),
                'confidence_level' => 80,
                'signal' => 'over',
                'reason' => 'Strong Q2 earnings beat expected. iPhone 17 launch driving bullish sentiment.',
                'detailed_summary' => 'Apple\'s latest earnings report exceeded analyst expectations by 12%. With the iPhone 17 cycle beginning and services revenue at an all-time high, technical indicators show a breakout above the $200 resistance level. RSI remains healthy at 58.',
                'status' => 'active',
                'win_rate' => null,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 3,
                'title' => 'TSLA — Tesla Inc.',
                'scheduled_at' => now()->subDays(5)->setTime(9, 30),
                'confidence_level' => 70,
                'signal' => 'under',
                'reason' => 'Delivery numbers missed Q1 targets. Increased EV competition from BYD pressuring margins.',
                'detailed_summary' => 'Tesla\'s Q1 deliveries came in 8% below consensus estimates. Chinese competitors are aggressively pricing EVs in key markets. The stock is showing bearish divergence on the weekly chart with declining volume on up days.',
                'status' => 'win',
                'win_rate' => 70.00,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 3,
                'title' => 'NVDA — NVIDIA Corp.',
                'scheduled_at' => now()->addDays(5)->setTime(9, 30),
                'confidence_level' => 88,
                'signal' => 'over',
                'reason' => 'AI chip demand continues to surge. Data center revenue growing 200% YoY.',
                'detailed_summary' => 'NVIDIA remains the dominant supplier of AI training chips. With major cloud providers continuing to expand GPU capacity and no credible competitor in the near term, revenue visibility is exceptionally high. Forward P/E is elevated but justified by growth trajectory.',
                'status' => 'active',
                'win_rate' => null,
                'created_by' => $adminId,
            ],

            // Crypto
            [
                'category_id' => 4,
                'title' => 'BTC/USD — Bitcoin',
                'scheduled_at' => now()->addHours(12),
                'confidence_level' => 82,
                'signal' => 'over',
                'reason' => 'Post-halving momentum building. Institutional inflows via ETFs accelerating.',
                'detailed_summary' => 'Bitcoin is 30 days past its fourth halving event. Historically, BTC enters a sustained bull phase 30-90 days post-halving. Spot ETF inflows have exceeded $500M in the past week alone. Key resistance at $72K — a close above targets $85K next.',
                'status' => 'active',
                'win_rate' => null,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 4,
                'title' => 'ETH/USD — Ethereum',
                'scheduled_at' => now()->subDays(4)->setTime(14, 0),
                'confidence_level' => 75,
                'signal' => 'over',
                'reason' => 'Ethereum ETF approval expected. Network staking yield attractive to institutional holders.',
                'detailed_summary' => 'Multiple asset managers have filed for spot Ethereum ETFs. Approval odds are rising following Bitcoin ETF precedent. On-chain data shows declining exchange supply as more ETH is locked in staking. Support at $3,200 remains firm.',
                'status' => 'win',
                'win_rate' => 75.00,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 4,
                'title' => 'SOL/USD — Solana',
                'scheduled_at' => now()->subDays(10)->setTime(10, 0),
                'confidence_level' => 63,
                'signal' => 'under',
                'reason' => 'Network congestion issues resurfacing. Key DeFi protocols migrating to competing chains.',
                'detailed_summary' => 'Solana has experienced repeated network outages over the past month, eroding developer confidence. Two major DeFi protocols have announced migration plans to alternative L1s. Short-term correction to $120 support zone appears likely before any recovery.',
                'status' => 'loss',
                'win_rate' => 63.00,
                'created_by' => $adminId,
            ],
        ];

        foreach ($predictions as $prediction) {
            Prediction::create($prediction);
        }
    }
}
