<?php

/*
|--------------------------------------------------------------------------
| TraceMem — Memory Decision Engine Configuration
|--------------------------------------------------------------------------
|
| This file controls the deterministic MemoryDecisionEngine.
| No AI. No HTTP. Pure PHP rule evaluation.
|
| Versioning
| ──────────
| engine_version — bump when the evaluation algorithm changes
| rule_version   — bump when rules (patterns, weights, ids) change
|
| Both versions are stamped into every memory stored via /chat,
| giving you full traceability: "which engine and which rule set
| created this memory?"
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Version Tracking
    |--------------------------------------------------------------------------
    */
    'engine_version' => (int) env('MEMORY_ENGINE_VERSION', 1),
    'rule_version'   => (int) env('MEMORY_RULES_VERSION', 1),

    /*
    |--------------------------------------------------------------------------
    | Confidence Threshold (Threshold-Anchored — NOT max-score-anchored)
    |--------------------------------------------------------------------------
    |
    | confidence = min(matched_weight / confidence_threshold_weight, 1.0)
    |
    | confidence_threshold_weight is a FIXED constant. Adding new rules
    | NEVER changes this value. New rules never silently lower confidence
    | for old messages.
    |
    | To store a memory: confidence must reach confidence_store_threshold.
    |
    | near_miss_low_threshold: confidence >= this AND < store threshold
    | triggers a near-miss log entry (never stored, but logged for tuning).
    |
    */
    'confidence_threshold_weight' => (int)   env('MDE_THRESHOLD_WEIGHT', 100),
    'confidence_store_threshold'  => (float) env('MDE_STORE_THRESHOLD', 0.55),
    'near_miss_low_threshold'     => (float) env('MDE_NEAR_MISS_LOW', 0.40),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags — Rule Groups
    |--------------------------------------------------------------------------
    |
    | Disable any rule group at runtime via env var — no redeployment needed.
    | Negative rules should almost never be disabled in production.
    |
    */
    'feature_flags' => [
        'enable_negative_rules'      => (bool) env('MDE_NEGATIVE_RULES', true),
        'enable_skip_rules'          => (bool) env('MDE_SKIP_RULES', true),
        'enable_imperative_rules'    => (bool) env('MDE_IMPERATIVE_RULES', true),
        'enable_identity_rules'      => (bool) env('MDE_IDENTITY_RULES', true),
        'enable_preference_rules'    => (bool) env('MDE_PREFERENCE_RULES', true),
        'enable_fact_rules'          => (bool) env('MDE_FACT_RULES', true),
        'enable_skill_rules'         => (bool) env('MDE_SKILL_RULES', true),
        'enable_habit_rules'         => (bool) env('MDE_HABIT_RULES', true),
        'enable_goal_rules'          => (bool) env('MDE_GOAL_RULES', true),
        'enable_constraint_rules'    => (bool) env('MDE_CONSTRAINT_RULES', true),
        'enable_tool_rules'          => (bool) env('MDE_TOOL_RULES', true),
        'enable_communication_rules' => (bool) env('MDE_COMMUNICATION_RULES', true),
        'enable_telemetry'           => (bool) env('MDE_TELEMETRY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Active Locales
    |--------------------------------------------------------------------------
    |
    | Only rules belonging to these locales are loaded. Add locale namespaces
    | here when multilingual rules are ready. Zero cost for empty locales.
    |
    */
    'active_locales' => ['en'],

    /*
    |--------------------------------------------------------------------------
    | Rule Registry
    |--------------------------------------------------------------------------
    |
    | Rules are namespaced by locale. Each rule is an array with:
    |
    |   id          — unique dotted identifier, e.g. "identity.name"
    |   type        — memory type: fact | preference | rule | skill
    |   group       — feature-flag group: negative | skip | identity | ...
    |   priority    — evaluation order (lower = evaluated first)
    |   weight      — contribution to confidence score (0–110+)
    |   terminal    — if true, first match halts all further evaluation
    |   enabled     — false = never loaded (use feature_flags for runtime toggle)
    |   description — human-readable label
    |   patterns    — array of PCRE regex strings (any match = rule fires)
    |   volatility  — 'persistent' | 'volatile' (internal; not exposed via API)
    |   reason_code — from DecisionReasonCode constants
    |
    | Priority bands:
    |   10–19   negative rules (always first)
    |   20–29   skip rules
    |   30–39   imperative rules
    |   40–59   high-confidence identity rules
    |   60–79   fact / dietary / location rules
    |   80–99   preference / constraint rules
    |   100–119 skill / habit / goal rules
    |   120–139 tool / communication rules
    |
    */
    'locales' => [

        // en - main 
        'en' => [

            // ── NEGATIVE RULES (priority 10–19, all terminal) ──────────────────
            // Evaluated before everything else. A match = never store.
            // These are NOT greetings — they are messages that look like
            // personal facts but are hypothetical, fictional, or instructional.

            [
                'id'          => 'negative.roleplay',
                'type'        => 'fact',
                'group'       => 'negative',
                'priority'    => 10,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'NEGATIVE_RULE_MATCH',
                'description' => 'Roleplay / pretend scenario — never store',
                'patterns'    => [
                    // 1. Core Pretend & Act Verbs (Broad Persona Assignment)
                    "/\\b(pretend|act|play|simulate|emulate|mimic|cosplay|rp)(\\s+as|\\s+like|\\s+out)?\\b/i",
                    
                    // 2. Persona Hooks & Creative Mandates (Chill, Sophisticated, Coder)
                    "/\\b(you('re|\\s+are)|i('m|\\s+am)|we('re|\\s+are)|user\\s+is|ai\\s+is)\\s+(a|an|the|now)?\\s*(roleplaying|playing|acting|pretending|simulating|assuming|hypothesizing)\\b/i",
                    "/\\b(adopt|take\\s+on|step\\s+into|write\\s+from\\s+the)\\s+(the\\s+)?(persona|role|shoes|perspective|character|voice)\\b/i",

                    // 3. Narrative & Storytelling Frameworks (Writers, Kids, Regular)
                    "/\\b(in|for|during)\\s+this\\s+(story|scenario|roleplay|game|simulation|universe|timeline|setting|fable|novel|script|prompt)\\b/i",
                    "/\\b(write|create|generate|tell|make\\s+up)\\s+(a|an)?\\s*(story|script|dialogue|play|fanfic|skit|scene|narrative)\\b/i",
                    
                    // 4. Hypothetical & Suppositional Setups (Sophisticated, Coders, Adults)
                    "/\\b(imagine|suppose|hypothesize|assume|let's\\s+say|what\\s+if|for\\s+the\\s+sake\\s+of\\s+argument)\\b/i",
                    "/\\b(hypothetical|fictional|make-believe|counterfactual|mock|sandbox)\\s+(scenario|setup|context|example|user|world)\\b/i",

                    // 5. Shortened & Text Slang (Chill, Gaming, Casual Typing)
                    "/\\b(let's\\s+rp|wanna\\s+rp|open\\s+rp|character\\s+ai|oc\\s+x\\s+oc|pov:)\\b/i"
                ],
            ],

            [
                'id'          => 'negative.hypothetical',
                'type'        => 'fact',
                'group'       => 'negative',
                'priority'    => 11,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'NEGATIVE_RULE_MATCH',
                'description' => 'Hypothetical / imagine scenario — never store',
                'patterns'    => [
                    // 1. Speculative Thought Hooks (Sophisticated, Writer, Adult)
                    "/\\b(imagine|suppose|supposed|postulate|propose|extrapolate|conjecture)\\s+(that\\s+)?(i|my|we|you|the|a|an|if)\\b/i",
                    "/\\b(hypothetically|theoretically|conceptually|philosophically|abstractly)[,:]?\\s*(if|that|should)?\\s*(i|my|we|you|a|the)\\b/i",

                    // 2. Casual Speculation Starters (Chill, Child, Regular Typing)
                    "/\\blet['’]s\\s+say\\s+(that\\s+)?(i|my|we|you|a|the)\\b/i",
                    "/\\bwhat\\s+if\\s+(i|my|we|you|they|everything|something)\\b/i",
                    "/\\b(think|thinking)\\s+about\\s+(if|how|a\\s+world\\s+where)\\b/i",
                    "/\\bjust\\s+(for\\s+fun|curious|asking|wondering|pondering)\\b/i",

                    // 3. Subjunctive & Counterfactual Rules (Writers, Sophisticated, Coders)
                    "/\\bif\\s+(i|we|you|it)\\s+(were|was|had|could|would|should|became|ended\\s+up)\\b/i",
                    "/\\b(even|only|what|but)\\s+if\\s+(i|my|we|you|it)\\s+(had|did|was|were)\\b/i",
                    "/\\bfor\\s+the\\s+sake\\s+of\\s+(argument|example|discussion|demonstration)\\b/i",

                    // 4. Case Studies & Mocking Signals (Coder, Professional, Regular)
                    "/\\b(as\\s+a|for\\s+a|sample|mock|test|dummy|example)\\s+(case|study|scenario|example|dilemma|setup|datapoint)\\b/i",
                    "/\\b(let['’]s\\s+)?run\\s+a\\s+(mental|thought|simulation)\\s+(exercise|experiment)\\b/i",

                    // 5. Shortened / Text-Style Speculation (Chill, Text-Speak)
                    "/\\b(ex:|eg:|e\\.g\\.|w\\/o\\s+actually|hypothetical\\s+q:)\\b/i"
                ],
            ],

            [
                'id'          => 'negative.example',
                'type'        => 'fact',
                'group'       => 'negative',
                'priority'    => 12,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'NEGATIVE_RULE_MATCH',
                'description' => 'Example or illustration — never store',
                'patterns'    => [
                    // 1. Explicit Example Indicators (Sophisticated, Regular, Professional)
                    "/\\b(this\\s+is\\s+an?|here['’]s\\s+an?|take\\s+the|concrete)\\s+(example|instance|illustration|case\\s+in\\s+point)\\b/i",
                    "/\\bfor\\s+(example|instance)[,:]?\\s*(i|my|you|we|a|the|someone|john|doe)\\b/i",
                    "/^(example|illustration|case\\s+study|scenario)[:\\s-]/i",

                    // 2. Technical and Mock Data Formats (Coder, QA, Technical Writer)
                    "/\\b(sample|mock|test|dummy|placeholder|fake|synthetic)\\s+(message|input|text|data|payload|string|user|profile|email|name)[:\\s-]?/i",
                    "/\\b(json|xml|csv|request|response)\\s+(sample|example|payload|template)\\b/i",
                    "/\\bfoo(bar)?\\b|\\bbaz\\b|\\bqux\\b/i", // Standard developer placeholder variables

                    // 3. Conversational / Loose Examples (Chill, Casual, Child)
                    "/\\b(like\\s+if|say\\s+if|just\\s+like|as\\s+in)[,:\\s]+(i|my|you|someone|your|a\\s+guy)\\b/i",
                    "/\\b(e\\.g\\.|ex|eg)[:\\s,]+(i|my|the|a)\\b/i",
                    "/\\b(show\\s+you|show\\s+me|give\\s+you)\\s+what\\s+i\\s+mean\\b/i"
                ],
            ],

            [
                'id'          => 'negative.translate',
                'type'        => 'fact',
                'group'       => 'negative',
                'priority'    => 13,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'NEGATIVE_RULE_MATCH',
                'description' => 'Translation request — content is not about the user',
                'patterns'    => [
                    // 1. Direct Commands and Strict Framing (Sophisticated, Regular, Professional)
                    "/^(translate|translation|interpret|render)[:\\s-]/i",
                    "/\\b(translate|translation|interpret|transliterate|rephrase)(\\s+this|\\s+the\\s+following|\\s+content)?\\s+(into|to|in)\\b/i",
                    "/\\b(translation|version|rendering|equivalent)\\s+of\\s+(the|this|my|your|string)\\b/i",

                    // 2. Multilingual Target and Source Swaps (Writer, Coder, Global Users)
                    "/\\b(how\\s+do\\s+you\\s+say|what\\s+is|give\\s+me)\\b.*\\b(in|into|to)\\s+(english|spanish|french|german|chinese|japanese|hindi|arabic|portuguese|russian|italian|dutch|korean|vietnamese|thai|hebrew|swedish|polish|latin)\\b/i",
                    "/\\b(change|convert|switch|turn|rewrite)\\s+this\\s+(text|phrase|word|sentence|from\\s+[a-z]+)?\\s+(into|to)\\b/i",

                    // 3. Conversational and Lazy Slang Queries (Chill, Casual, Child)
                    "/\\b(what['’]s|what\\s+is)\\s+this\\s+mean\\s+in\\b/i",
                    "/\\b(say\\s+this\\s+in|put\\s+this\\s+in|write\\s+this\\s+in)[\\s:]/i",
                    "/\\bmeaning\\s+of\\s+[\"'].*[\"']\\s+(in|into)\\b/i"
                ],
            ],

            [
                'id'          => 'negative.joke',
                'type'        => 'fact',
                'group'       => 'negative',
                'priority'    => 14,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'NEGATIVE_RULE_MATCH',
                'description' => 'Joke or sarcasm framing — never store',
                'patterns'    => [
                    // 1. Direct Humorous Disclaimers & Acronyms (Chill, Casual, Child)
                    "/\\b(just\\s+kidding|jk|j\\/k|j-k|jk\\b.*|i['’]m\\s+kidding|only\\s+kidding)\\b/i",
                    "/\\b(as\\s+an?\\s+)?(joke|prank|gag|pun|spoof|satire|meme|shitpost|sarcasm|sarcastic|facetious)(ly)?\\b/i",
                    "/\\b(haha|lmao|lol|rofl|lmfao|hehe|xd|kuku|wkwk|55555)\\b/i",

                    // 2. Irony & Conversational Deflections (Sophisticated, Writer, Adult)
                    "/\\b(don['’]t\\s+take\\s+this|not\\s+being)\\s+(serious|literal|real)\\b/i",
                    "/\\bfor\\s+shits\\s+and\\s+giggles\\b|\\bjust\\s+for\\s+laughs\\b/i",
                    "/\\b(obviously|clearly|clearly\\s+a)\\s+(joke|sarcasm|satirical)\\b/i",
                    
                    // 3. Narrative Sarcasm Anchors (Regular Typing, Coders)
                    "/\\b(picture\\s+this|imagine|adds)[\\s\\w:]*\\b(sarcasm|\\/s|:p|:-p|🤪|😂|🤣)\\b/i",
                    "/\\bmy\\s+.*\\s+(is\\s+a\\s+joke|is\\s+hilarious|is\\s+cooked)\\b/i"
                ],
            ],

            [
                'id'          => 'negative.explicit_deny',
                'type'        => 'fact',
                'group'       => 'negative',
                'priority'    => 15,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'NEGATIVE_RULE_MATCH',
                'description' => 'Explicit user instruction not to store',
                'patterns'    => [
                    // 1. Direct Commands & Strict Prohibitions (Sophisticated, Regular, Professional)
                    "/\\b(do\\s+not|don['’]t|never|cease|stop)\\s+(store|remember|save|retain|record|commit\\s+to|keep|log)\\s+(this|that|my|our|the\\s+following|info|data|fact|context)\\b/i",
                    "/\\b(omit|exclude|delete|wipe|erase)\\s+from\\s+(your\\s+)?(memory|database|history|storage|profile|records)\\b/i",
                    "/\\b(forget\\s+everything|forget)\\s+(this|what\\s+i\\s+(just|ever)\\s+said|the\\s+last\\s+part)\\b/i",

                    // 2. Fragmented or Absolute Denials (Chill, Coder, Casual Typing)
                    "/\\b(not\\s+for\\s+(memory|storage|saving|retaining|keeping))\\b/i",
                    "/\\b(no\\s+memory|zero\\s+memory|dont\\s+save|do\\s+not\\s+save|off\\s+the\\s+record|incognito\\s+mode)\\b/i",
                    "/\\b(skip\\s+(storing|saving|this|memory))\\b/i",

                    // 3. Conversational / Loose Disclaimers (Chill, Text-Speak, Child)
                    "/\\b(ignore\\s+this|scratch\\s+that|disregard\\s+(this|my\\s+last))\\b/i",
                    "/\\b(don['’]t\\s+need\\s+to\\s+remember|no\\s+need\\s+to\\s+store|you\\s+can\\s+forget\\s+this)\\b/i",
                    "/\\b(just\\s+passing\\s+through|temporary\\s+thought|don['’]t\\s+worry\\s+about\\s+saving)\\b/i"
                ],
            ],


            // ── SKIP RULES (priority 20–29, terminal) ──────────────────────────
            // Greetings, questions, tasks, math — never personal facts.

            [
                'id'          => 'skip.greeting',
                'type'        => 'fact',
                'group'       => 'skip',
                'priority'    => 20,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'SKIP_GREETING',
                'description' => 'Greeting or salutation',
                'patterns'    => [
                    // 1. Direct Casual & Strict Standalone Openers (Chill, Casual, Child)
                    "/^(hi|hello|hey|yo|sup|howdy|hiya|greetings|salutations|holas?|oi|wassup|wazzup)[\\s!.,?~]*$/i",
                    "/^(good\\s+(morning|afternoon|evening|night|day|afternoon|morrow))([\\s!.,?]*|$)/i",
                    "/^(heyy+|hiii+|helloww+)([\\s!.,?]*|$)/i", // Loose repeated characters from casual typing

                    // 2. Conversational Status Queries (Regular, Sophisticated, Professional)
                    "/^how\\s+(are\\s+you|is\\s+it\\s+going|are\\s+things|s\\s+everything|goes\\s+it|have\\s+you\\s+been)[\\s?.]*/i",
                    "/^what['’]s\\s+(up|new|the\\s+good\\s+word|going\\s+on|happenin[g']?)[\\s?.]*/i",
                    "/^(hope\\s+you['’]re\\s+(doing\\s+well|having\\s+a\\s+good\\s+day))[\\s!.]*/i",

                    // 3. Meeting & Interactive Pleasantries (Writer, Adult, Polished)
                    "/^(nice|good|great|pleasure)\\s+to\\s+(meet|see|connect\\s+with|chat\\s+with)\\s+you[\\s!.]*/i",
                    "/^long\\s+time\\s+no\\s+(see|hear|talk)[\\s!.]*/i",
                    "/^quick\\s+question[\\s!.,:]*/i" 
                ],
            ],

            [
                'id'          => 'skip.question',
                'type'        => 'fact',
                'group'       => 'skip',
                'priority'    => 21,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'SKIP_QUESTION',
                'description' => 'General knowledge question or factual query',
                'patterns'    => [
                    // 1. Direct Factual & General Knowledge Inquiries (Sophisticated, Regular, Professional)
                    "/^(what|who|how|why|when|where|which|whose)\\s+(is|are|was|were|does|do|did|can|could|should|would|has|have|mean[s]?)\\b/i",
                    "/\\b(tell\\s+me|explain|describe|summarize|summarise|clarify|define|elaborate|expound)\\s+(about|what|how|why|the|a|an|meaning|concept|history)\\b/i",
                    "/^(can|could|would|will|may|please)\\s+(you\\s+)?(tell|explain|describe|show|list|give|provide|summarize|break\\s+down)\\b/i",

                    // 2. Real-Time, News, Sports, & Environmental Queries (Chill, Casual, Every User)
                    "/^(who\\s+won|what\\s+happened|latest\\s+news|score\\s+of|result\\s+of|updates\\s+on|current\\s+status\\s+of)\\b/i",
                    "/\\b(weather|temperature|forecast|rain|snow|climate|aqi|degrees|forecasted)\\b/i",
                    "/^(how\\s+much|how\\s+many|how\\s+far|how\\s+long|how\\s+fast|what\\s+time)\\b/i",

                    // 3. Conversational / Shortened Informational Triggers (Chill, Text-Speak, Child)
                    "/\\b(info|information|details|specs|stats|facts|background|history|tl[;:-]?dr)\\s+(on|about|for)\\b/i",
                    "/\\b(anybody\\s+know|anyone\\s+know|quick\\s+q|quick\\s+question|curious\\s+about)\\b/i",
                    "/^[?❓¿\s]*$/i" // Catches single-punctuation or pure emoji informational prompts
                ],
            ],

            [
                'id'          => 'skip.task',
                'type'        => 'fact',
                'group'       => 'skip',
                'priority'    => 22,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'SKIP_TASK',
                'description' => 'Content generation or task request',
                'patterns'    => [
                    // 1. Creative and Engineering Directives (Coder, Writer, Professional)
                    "/^(write|generate|create|make|build|design|compose|draft|produce|compile|setup|deploy)\\b/i",
                    "/^(fix|debug|refactor|improve|optimize|review|format|correct|clean|minify|lint|audit)\\b/i",
                    "/^(define|list|give\\s+me|show\\s+me|provide|display|output|render|extract|fetch)\\b/i",

                    // 2. Structural & Format Transformations (Sophisticated, Coder, Technical Writer)
                    "/^(translate|convert|transform|parse|switch|change|shift|port|migrate|serialize|deserialize)\\s+(this|it|that|code|text|file|string|data)\\b/i",
                    "/\\b(into|to|from)\\s+(json|xml|csv|yaml|html|markdown|md|sql|php|javascript|typescript|ts|js|css)\\b/i",

                    // 3. Collaborative Assistance & Task Onboarding (Chill, Casual, Every User)
                    "/^(help\\s+me\\s+(with|to|understand|fix|build|write|create|debug|solve|figure\\s+out|code|do))\\b/i",
                    "/\\b(can\\s+you|could\\s+you|mind|please)\\s+(help|assist|handle|take\\s+care\\s+of|do|work\\s+on)\\b/i",
                    "/\\b(your\\s+task\\s+is|i\\s+need\\s+you\\s+to|i\\s+want\\s+you\\s+to|go\\s+ahead\\s+and)\\b/i"
                ],
            ],

            [
                'id'          => 'skip.math',
                'type'        => 'fact',
                'group'       => 'skip',
                'priority'    => 23,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'SKIP_MATH',
                'description' => 'Mathematical expression or pure computation',
                'patterns'    => [
                    // 1. Pure Arithmetic & Symbolic Expressions (Every User, Regular, Casual)
                    "/^[\\d\\s\\+\\-\\*\\/\\^%=\\(\\)\\.\\,\\x{00d7}\\x{00f7}]+[=?\\s]*$/u", // Includes standard math operators like × and ÷
                    "/^\\d+\\s*([\\+\\-\\*\\/\\^%]|x|\\x{00d7}|\\x{00f7})\\s*\\d+/iu",
                    "/^[\\d\\s\\+\\-\\*\\/\\(\\)]+=[\\s\\d\\?]*$/",

                    // 2. Direct Computation & Evaluation Commands (Sophisticated, Coder, Professional)
                    "/^(calculate|compute|solve|evaluate|find\\s+the\\s+sum\\s+of|integrate|differentiate|simplify)\\s+([\\d\\(x\\s]|matrix|equation|integral|derivative|fraction)/i",
                    "/\\b(square\\s+root|sqrt|log|ln|factorial|sin|cos|tan|log2|log10|percentage\\s+of)\\b/i",
                    "/\\b(math|equation|formula|expression|calculus|algebra|geometry|matrix)[:\\s-]/i",

                    // 3. Conversational Math & Financial Queries (Chill, Casual, Child)
                    "/\\b(how\\s+much\\s+is|what\\s+is|what['’]s)\\s+(\\d+|\\d+%.*|\\$\\d+|half\\s+of|double\\s+of)\\b/i",
                    "/\\b(plus|minus|times|divided\\s+by|multiplied\\s+by)\\s+\\d+\\b/i",
                    "/\\b(add|subtract|multiply|divide)\\s+\\d+\\s+(to|from|by)\\s+\\d+\\b/i"
                ],
            ],

            [
                'id'          => 'skip.joke',
                'type'        => 'fact',
                'group'       => 'skip',
                'priority'    => 24,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'SKIP_JOKE',
                'description' => 'Joke or entertainment request',
                'patterns'    => [
                    // 1. Direct Entertainment & Humorous Prompts (Child, Casual, Regular Typing)
                    "/\\b(tell|crack|give|share|say)\\s+(me\\s+)?(a\\s+)?(joke|pun|riddle|gag|story|one-liner|wisecrack)\\b/i",
                    "/\\bmake\\s+me\\s+(laugh|giggle|chuckle|smile)\\b/i",
                    "/\\b(something|anything)\\s+(funny|hilarious|humorous|entertaining|amusing)\\b/i",

                    // 2. Persona Requests & Creative Comedy (Sophisticated, Writer, Adult)
                    "/\\b(write|roast|satirize|parody|spoof|mimic|entertain)\\s+(me|my|a|the)\\b/i",
                    "/\\b(comedic|standup|satirical|witty|ironic|sarcastic|playful)\\s+(take|monologue|script|skit|response|commentary)\\b/i",
                    "/\\b(cheer\\s+me\\s+up|need\\s+a\\s+laugh|crack\\s+up)\\b/i",

                    // 3. Internet Slang & Lazy Triggers (Chill, Text-Speak, Coder)
                    "/\\b(meme\\s+me|hit\\s+me\\s+with\\s+a|dad\\s+joke|puns|shitpost)\\b/i",
                    "/\\b(lol|haha|lmao|xd)\\s+(tell|show|write)\\b/i",
                    "/^[\\s]*\\b(joke|pun|riddle)\\b[\\s?❓]*$/i" // Single word triggers or wrapped with punctuation
                ],
            ],

            [
                'id'          => 'skip.short',
                'type'        => 'fact',
                'group'       => 'skip',
                'priority'    => 25,
                'weight'      => 0,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'volatile',
                'reason_code' => 'SKIP_SHORT',
                'description' => 'Message too short to contain personal information',
                'patterns'    => [
                    // 1. Fragmented & Single-Word Typers (Chill, Casual, Child)
                    "/^[a-z\\s!.,?\\-]{1,15}$/i",
                    "/^.{1,8}$/u", // Catch-all for extreme brevity across all character sets (e.g., "ok", "cool", "wait", "huh", "omg")
                    "/^\\b(yes|no|y|n|ok|okay|kk|k|yup|nah|sure|fine|cool|nice|wait|stop|go|next|back|done|exit|quit|void|null|true|false)\\b[\\s!.,?~]*$/i",

                    // 2. Pure Punctuation, Interjections & Emojis (Chill, Casual Text-Speak)
                    "/^[\\s!.,?\\/\\\\#@$%^&*()_+\\-={}\\[\\]|:;\"'<>,~`❓❗+]*$/", // Pure punctuation or syntax typing mistakes
                    "/^[\\s\\x{1F600}-\\x{1F64F}\\x{1F300}-\\x{1F5FF}\\x{1F680}-\\x{1F6FF}\\x{2600}-\\x{26FF}\\x{2700}-\\x{27BF}!.,?]*$/u", // Pure emoji/reaction inputs

                    // 3. Command Fragments & Terminal Inputs (Coder, Technical User)
                    "/^[:\\/\\\\.-]?[a-z0-9_]{1,10}$/i", 
                    "/^\\b(test|run|exec|clear|cls|ping|pong|help|info|status|get|post)\\b[\\s]*$/i"
                ],
            ],


            // ── IMPERATIVE RULES (priority 30–39, terminal, forces store) ───────
            // Explicit user instructions to remember something.

            [
                'id'          => 'imperative.remember',
                'type'        => 'fact',
                'group'       => 'imperative',
                'priority'    => 30,
                'weight'      => 110,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'IMPERATIVE_REMEMBER',
                'description' => 'Explicit "remember" instruction',
                'patterns'    => [
                    // 1. Explicit Memory Injunctions & Commands (Sophisticated, Regular, Professional)
                    "/\\b(always\\s+)?remember\\s+(that|this|to|me|us|:)?\\b/i",
                    "/\\b(please\\s+)?(remember|note|take\\s+note|bear\\s+in\\s+mind|keep\\s+in\\s+mind)\\s+(that|this|how|the\\s+following)?\\b/i",
                    "/\\b(don['’]t|never|do\\s+not)\\s+forget\\s+(that|this|to|i|my|our)\\b/i",
                    "/\\b(make\\s+sure|ensure|it\\s+is\\s+crucial|it['’]s\\s+important)\\s+to\\s+(remember|note|keep\\s+in\\s+mind)\\b/i",

                    // 2. High-Priority Logging & Reference Anchors (Coder, Technical Writer)
                    "/\\b(mark\\s+this|save\\s+this|store\\s+this|log\\s+this|commit\\s+this\\s+to\\s+memory)\\b/i",
                    "/\\b(important|crucial|critical|note|nb|n\\.b\\.|takeaway)[:\\s-]/i",
                    "/\\b(bookmark|pin|archive|file\\s+this\\s+under)\\b/i",

                    // 3. Conversational Reminders & Loose Cues (Chill, Casual, Child)
                    "/\\b(just\\s+so\\s+you\\s+know|jsyk|fyi|for\\s+your\\s+info|by\\s+the\\s+way|btw)\\b/i",
                    "/\\b(remind\\s+me|remind\\s+yourself|keep\\s+track\\s+of)\\b/i",
                    "/\\b(write\\s+this\\s+down|put\\s+this\\s+down|add\\s+this)\\b/i"
                ],
            ],

            [
                'id'          => 'imperative.store',
                'type'        => 'fact',
                'group'       => 'imperative',
                'priority'    => 31,
                'weight'      => 110,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'IMPERATIVE_REMEMBER',
                'description' => 'Implicit imperative store phrases',
                'patterns'    => [
                    // 1. Direct Voluntary Logging & Database Injection Commands (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(want\\s+you\\s+to|need\\s+you\\s+to|would\\s+like\\s+you\\s+to)\\s+(remember|store|save|record|retain|log|memorize)\\b/i",
                    "/\\b(make\\s+an?\\s+(note|entry)|take\\s+a\\s+note)\\s+(that|of|down|about)?\\b/i",
                    "/\\b(save|store|record|register|archive|capture)\\s+(this|that|the\\s+fact|my\\s+info|our\\s+chat|this\\s+profile|this\\s+context)\\b/i",
                    "/\\badd\\s+(this|that|the\\s+following)\\s+to\\s+(my|your)?\\s*(memory|profile|database|records|history|notes)\\b/i",

                    // 2. Fragmented/Sloppy Command Imperatives (Chill, Casual, Text-Speak)
                    "/\\b(remember\\s+this|save\\s+this|store\\s+this|lock\\s+this\\s+in|write\\s+it\\s+down|put\\s+it\\s+down)\\b/i",
                    "/\\b(dont\\s+forget\\s+this|do\\s+not\\s+forget\\s+this|keep\\s+this)\\b/i",
                    "/\\b(memorize\\s+this|file\\s+this\\s+away)\\b/i",

                    // 3. Technical Reference & Persist Triggers (Coder, Power User)
                    "/\\b(persist|commit|upsert|insert|update|log|cache)\\s+(this|context|state|data|fact|variable|profile)\\b/i",
                    "/\\b(memory|profile|bio|ctx|meta)\\s*(update|addition|save|+=)[:\\s-]/i"
                ],
            ],


            // ── IDENTITY RULES (priority 40–49, terminal on strong match) ───────

            [
                'id'          => 'identity.name',
                'type'        => 'fact',
                'group'       => 'identity',
                'priority'    => 40,
                'weight'      => 100,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'IDENTITY_NAME_MATCH',
                'description' => 'User declares their name or alias',
                'patterns'    => [
                    // 1. Direct and Formal Name Declarations (Sophisticated, Regular, Professional)
                    "/\\bmy\\s+(first\\s+|last\\s+|full\\s+|legal\\s+)?name\\s+(is|gets\\s+spelled)\\b/i",
                    "/\\bi\\s+(go\\s+by|am\\s+called|was\\s+named)\\b/i",
                    "/\\b(you\\s+can\\s+|please\\s+)?call\\s+me\\b/i",
                    "/\\bmy\\s+(username|alias|handle|nickname|gamer\\s*tag|display\\s*name)\\s+(is|am|stands\\s+for)\\b/i",

                    // 2. Fragmented and Loose Identity Assertions (Chill, Casual, Child)
                    "/\\bi['’]m\\s+([a-z]+)\\s+by\\s+the\\s+way\\b/i",
                    "/\\b(everyone|people|my\\s+friends|the\\s+devs)\\s+call[s]?\\s+me\\b/i",
                    "/\\b(introducing\\s+myself|this\\s+is)\\s+([a-z0-9_-]+)\\b/i",
                    "/\\bmy\\s+initials\\s+are\\b/i",

                    // 3. Technical, Profile, and System Declarations (Coder, Power User)
                    "/\\b(user|profile|identity|account)\\s*(name|string|id)[:\\s-]/i",
                    "/\\b(set|change|update)\\s+(my|user)\\s+name\\s+to\\b/i"
                ],
            ],

            [
                'id'          => 'identity.pronouns',
                'type'        => 'fact',
                'group'       => 'identity',
                'priority'    => 41,
                'weight'      => 90,
                'terminal'    => true,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'IDENTITY_PRONOUN_MATCH',
                'description' => 'User declares their pronouns',
                'patterns'    => [
                    // 1. Explicit Pronoun Declarations & Slush Formats (Sophisticated, Regular, Professional)
                    "/\\bmy\\s+pronouns\\s+(are|is|:|\\bgo\\s+by\\b)\\b/i",
                    "/\\bi\\s+(use|prefer)\\s+([a-z]+)\\/([a-z\\/]+)(\\s+pronouns)?\\b/i", // Catches he/him, she/her, they/them, ze/zir, etc.
                    "/\\b(pronouns|preferred\\s+pronouns)[:\\s-]+([a-z]+)\\/([a-z\\/]+)/i",

                    // 2. Fragmented or Direct Imperative Forms (Chill, Casual, Child)
                    "/\\b(please\\s+)?(refer\\s+to\\s+me|call\\s+me|address\\s+me)\\s+(with|as|using)\\s+([a-z]+)\\/([a-z\\/]+)\\b/i",
                    "/\\b(refer\\s+to\\s+me\\s+as|call\\s+me\\s+a)\\s+\\b(guy|girl|man|woman|boy|lady|dude|enby|non-binary)\\b/i",
                    "/\\bi['’]m\\s+an?\\s+\\b(he|she|they)\\b\\s+person\\b/i",

                    // 3. Technical, Bio, and Meta Settings (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?pronouns\\s+to\\b/i",
                    "/\\b(profile|identity|meta|user)\\s*pronouns[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'identity.birthday',
                'type'        => 'fact',
                'group'       => 'identity',
                'priority'    => 42,
                'weight'      => 80,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'IDENTITY_BIRTHDAY_MATCH',
                'description' => 'User declares their birthday or age',
                'patterns'    => [
                    // 1. Direct Birthday & Date of Birth Milestones (Sophisticated, Regular, Professional)
                    "/\\bmy\\s+(birth\\s*day|birth\\s*date|date\\s+of\\s+birth|dob)\\s+(is|falls\\s+on)\\b/i",
                    "/\\bi\\s+(was\\s+born|came\\s+into\\s+the\\s+world)\\s+(on|in|back\\s+in)\\b/i",
                    "/\\b(date\\s+of\\s+birth|dob|born)[:\\s-]+(\\d{2,4}|[a-z]+)/i",

                    // 2. Direct Age Declarations & Aging Transitions (Chill, Casual, Child)
                    "/\\bi['’]m\\s+(\\d+|one|two|three|four|five|six|seven|eight|nine|ten)\\s*(years?\\s+old|yrs?\\s+old)?\\b/i",
                    "/\\bmy\\s+age\\s+is\\s*(\\b\\d+\\b|\\b[a-z]+\\b)/i",
                    "/\\bi\\s+(turn|turned|will\\s+be\\s+turning|hit)\\s+\\d+\\s+(this|next|last|coming)\\s+(year|month|week|tuesday)\\b/i",
                    "/\\bcelebrating\\s+my\\s+\\d+(th|st|rd|nd)?\\s+birth\\s*day\\b/i",

                    // 3. Technical, Registration, & Profile Formats (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(age|birthday)\\s+to\\b/i",
                    "/\\b(profile|user|account|meta)\\s*(age|birthday|birthdate)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'identity.contact',
                'type'        => 'fact',
                'group'       => 'identity',
                'priority'    => 43,
                'weight'      => 80,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'IDENTITY_CONTACT_MATCH',
                'description' => 'User declares email or phone',
                'patterns'    => [
                    // 1. Direct Contact Declarations (Sophisticated, Regular, Professional)
                    "/\\bmy\\s+(personal\\s+|work\\s+|primary\\s+)?(email|e-mail)(\\s+address)?\\s*(is|:|\\bgets\\s+spelled\\b)\\b/i",
                    "/\\bmy\\s+(cell\\s*phone|mobile|telephone|phone|whatsapp)(\\s+number)?\\s*(is|:)\\b/i",
                    "/\\b(contact|reach|ping|notify|message|email)\\s+me\\s+(at|on|via|through)\\b/i",
                    "/\\b(email|phone|mobile|tele|ph)[:\\s-]+/i",

                    // 2. Fragmented or Loose Contact Formats (Chill, Casual, Child)
                    "/\\b(here['’]s|this\\s+is)\\s+my\\s+(email|number|cell|phone|wp|discord)\\b/i",
                    "/\\bdrop\\s+me\\s+an?\\s+(email|line|text)\\s+at\\b/i",
                    "/\\bi\\s+(can\\s+be\\s+reached|usually\\s+use\\s+this\\s+number)\\b/i",
                    "/\\b(my\\s+digits\\s+are|text\\s+me\\s+at)\\b/i",

                    // 3. Technical, Profile, and Form Formats (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(email|phone|contact\\s+info)\\s+to\\b/i",
                    "/\\b(profile|user|account|meta|billing)\\s*(email|phone|contact|tel)[:\\s-]/i"
                ],
            ],


            // ── FACT RULES (priority 60–69) ────────────────────────────────────

            [
                'id'          => 'fact.location',
                'type'        => 'fact',
                'group'       => 'fact',
                'priority'    => 60,
                'weight'      => 75,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'FACT_LOCATION_MATCH',
                'description' => 'User declares their location or timezone',
                'patterns'    => [
                    // 1. Explicit Geographic & Base Positions (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(live|stay|reside|work|am\\s+based|am\\s+located|operate)\\s+in\\b/i",
                    "/\\bmy\\s+(home|current\\s+)?(city|country|state|region|town|village|location|hq|headquarters|office)\\s+(is|stands|falls\\s+in)\\b/i",
                    "/\\bi['’]m\\s+(originally\\s+)?from\\b/i",
                    "/\\bi\\s+(moved|relocated|immigrated|transferred)\\s+(to|from|into)\\b/i",

                    // 2. Loose & Casual Locality Cues (Chill, Casual, Child)
                    "/\\bi['’]m\\s+(in|at|around|down\\s+in|up\\s+in)\\s+([a-z]+)\\s+(right\\s+now|at\\s+the\\s+moment|area|direction)\\b/i",
                    "/\\b(just\\s+)?(moved\\s+to|got\\s+to|arrived\\s+in)\\b/i",
                    "/\\b(my\\s+neighborhood|my\\s+place|my\\s+hometown)\\s+is\\b/i",

                    // 3. Timezone, Offset, & Meta Configurations (Coder, Power User)
                    "/\\bmy\\s+(time\\s*zone|tz)\\s*(is|:|\\bdefaults\\s+to\\b)\\b/i",
                    "/\\bi['’]m\\s+in\\s+(the\\s+)?\\b(utc|gmt|est|edt|pst|pdt|ist|cet|cest|aest|cst|mst|bst|gmt[+-]\\d+)\\b/i",
                    "/\\b(set|change|update)\\s+(my\\s+)?(location|timezone|tz)\\s+to\\b/i",
                    "/\\b(profile|user|meta|system)\\s*(location|timezone|tz|geo|country)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'fact.job',
                'type'        => 'fact',
                'group'       => 'fact',
                'priority'    => 61,
                'weight'      => 75,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'FACT_JOB_MATCH',
                'description' => 'User declares their employer or job title',
                'patterns'    => [
                    // 1. Direct Professional Roles & Corporate Alignments (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(work|am\\s+employed)\\s+(at|for|by|with)\\b/i",
                    "/\\bmy\\s+(current\\s+)?(job|role|title|position|occupation|profession|career|company|employer|firm|org|organisation|organization)\\s*(is|:|\\bstands\\s+as\\b)\\b/i",
                    "/\\bi['’]m\\s+an?\\s+([a-z\\s_-]+)\\s+(at|for|with)\\b/i", // Catches "I'm a senior dev at..." or "I'm a writer for..."
                    "/\\bi\\s+(joined|started\\s+at|landed\\s+a\\s+job\\s+at)\\s+([a-z0-9\\s_-]+)\\s+(as|in\\s+the|to\\s+work)\\b/i",

                    // 2. Loose, Casual, or Freelance Employment Phrases (Chill, Casual, Coder)
                    "/\\bi\\s+(run|own|founded|lead|manage)\\s+(my\\s+own\\s+)?(startup|business|agency|shop|studio|practice)\\b/i",
                    "/\\bi['’]m\\s+(working|doing\\s+freelance|consulting|contracting)\\s+(full-time|part-time|on\\s+the\\s+side|indie)\\b/i",
                    "/\\bi\\s+build\\s+stuff\\s+(for|at)\\b/i",
                    "/\\bhere['’]s\\s+what\\s+i\\s+do\\s+for\\s+a\\s+living\\b/i",

                    // 3. System Data Layouts & Profile Schemes (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(job|role|title|company|employer)\\s+to\\b/i",
                    "/\\b(profile|user|meta|cv|resume)\\s*(job|role|title|position|company|employer|org)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'fact.profession',
                'type'        => 'fact',
                'group'       => 'fact',
                'priority'    => 62,
                'weight'      => 70,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'FACT_JOB_MATCH',
                'description' => 'User declares their profession or occupation',
                'patterns'    => [
                    // 1. Direct Professional Profiles & Tech Roles (Coder, Writer, Professional)
                    "/\\bi['’]m\\s+an?\\s+(software|backend|frontend|full.?stack|senior|junior|lead|staff|principal|growth|ios|android|web|cloud|ai|ml)?\\s*(developer|engineer|designer|architect|devops|sre|sysadmin|data\\s+scientist|analyst|pm|product\\s+manager|scrum\\s+master|qa|tester)\\b/i",
                    "/\\bi['’]m\\s+an?\\s+(student|teacher|professor|instructor|educator|doctor|nurse|physician|lawyer|attorney|accountant|auditor|entrepreneur|founder|co-founder|cto|ceo|cfo|cmo|vp|director)\\b/i",
                    "/\\bi\\s+(work|practice)\\s+as\\s+an?\\b/i",

                    // 2. Structural & Descriptive Industry Declarations (Sophisticated, Regular)
                    "/\\b(by\\s+profession|my\\s+trade|my\\s+background|by\\s+training)[,\\s]+i['’]m\\b/i",
                    "/\\bmy\\s+(chosen\\s+)?(profession|occupation|line\\s+of\\s+work|field|craft|industry)\\s*(is|:|\\bcenters\\s+around\\b)\\b/i",
                    "/\\bi\\s+earn\\s+a\\s+living\\s+as\\s+an?\\b/i",

                    // 3. Casual, Slang, & Domain-Specific Self-Identification (Chill, Casual, Coder)
                    "/\\bi['’]m\\s+an?\\s+\\b(writer|author|copywriter|editor|marketer|consultant|freelancer|artist|creator|videographer|photographer|indie\\s*hacker|builder|dev|coder|programmer|techie|sysop)\\b/i",
                    "/\\b(profile|user|meta|cv|resume)\\s*(profession|occupation|expertise|role|discipline)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'fact.dietary',
                'type'        => 'fact',
                'group'       => 'fact',
                'priority'    => 63,
                'weight'      => 80,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'FACT_DIETARY_MATCH',
                'description' => 'User declares dietary restrictions or medical conditions',
                'patterns'    => [
                    // 1. Direct Lifestyle, Cultural, & Medical Condition Declarations (Sophisticated, Regular, Professional)
                    "/\\bi['’]m\\s+an?\\s+\\b(vegetarian|vegan|pescatarian|flexitarian|fruitarian|halal|kosher|diabetic|celiac)\\b/i",
                    "/\\bi['’]m\\s+(lactose[\\-\\s]intolerant|gluten[\\-\\s]sensitive|gluten[\\-\\s]free|nut[\\-\\s]free|dairy[\\-\\s]free|soy[\\-\\s]free|keto|paleo)\\b/i",
                    "/\\bmy\\s+(diet|dietary\\s+restriction[s]?|medical\\s+condition|nutrition\\s+plan)\\s*(is|:|\\bdefaults\\s+to\\b)\\b/i",

                    // 2. Clear Allergy Warnings & Complete Avoidance Statements (Chill, Casual, Every User)
                    "/\\bi['’]m\\s+(highly\\s+)?allergic\\s+to\\s+([a-z\\s_-]+)\\b/i",
                    "/\\bi\\s+have\\s+an?\\s+([a-z\\s_-]+)?\\s*(allergy|intolerance|sensitivity)\\b/i",
                    "/\\bi\\s+(don['’]t|do\\s+not|never)\\s+eat\\s+([a-z\\s_-]+)\\b/i",
                    "/\\bi\\s+(completely\\s+)?(avoid|cut\\s+out|steer\\s+clear\\s+of|stay\\s+away\\s+from)\\s+([a-z\\s_-]+)\\b/i",

                    // 3. System Meta Configuration & Explicit Profile Fields (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(diet|dietary|allergies|allergy\\s+profile)\\s+to\\b/i",
                    "/\\b(profile|user|meta|health|dietary)\\s*(restrictions|allergies|diet|condition)[:\\s-]/i"
                ],
            ],


            // ── PREFERENCE RULES (priority 80–89) ─────────────────────────────

            [
                'id'          => 'preference.positive',
                'type'        => 'preference',
                'group'       => 'preference',
                'priority'    => 80,
                'weight'      => 70,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'PREFERENCE_MATCH',
                'description' => 'User expresses a positive preference',
                'patterns'    => [
                    // 1. Direct Affection, Affiliation, and Priority Selection (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(like|love|prefer|enjoy|adore|appreciate|favor|favour|gravitate\\s+towards|fancy)\\b/i",
                    "/\\bi['’]m\\s+an?\\s+(big\\s+|huge\\s+|massive\\s+)?(fan|advocate|admirer|supporter)\\s+of\\b/i",
                    "/\\bi\\s+(always\\s+)?(use|rely\\s+on|go\\s+with|stick\\s+to|choose|pick|select|opt\\s+for|lean\\s+towards)\\b/i",
                    "/\\bmy\\s+(go-to|preferred\\s+choice|favorite|favourite|number\\s+one|top\\s+pick|first\\s+choice)\\b/i",

                    // 2. Casual, Enthusiastic, and Opinionated Expressions (Chill, Casual, Child)
                    "/\\bi['’]m\\s+(really\\s+|super\\s+|dead\\s+|obsessed\\s+)?(into|keen\\s+on|fond\\s+of|crazy\\s+about)\\b/i",
                    "/\\b(nothing\\s+beats|i\\s+can['’]t\\s+get\\s+enough\\s+of|i['’]m\\s+down\\s+for)\\b/i",
                    "/\\b(i\\s+swear\\s+by|that['’]s\\s+my\\s+jam|that['’]s\\s+my\\s+thing)\\b/i",
                    "/\\bi\\s+(strongly\\s+)?prefer\\b/i",

                    // 3. Technical Configs & Personal Profile Schemas (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(preferences|favorites|faves|likes)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings)\\s*(preference|preferences|fav|favorite|likes)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'preference.negative',
                'type'        => 'preference',
                'group'       => 'preference',
                'priority'    => 81,
                'weight'      => 70,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'PREFERENCE_NEGATIVE_MATCH',
                'description' => 'User expresses a negative preference or aversion',
                'patterns'    => [
                    // 1. Direct Dislike, Strong Aversions, and Rejections (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(hate|dislike|despise|detest|loathe|can['’]t\\s+stand|absolutely\\s+hate)\\b/i",
                    "/\\bi['’]m\\s+not\\s+an?\\s+(big\\s+|huge\\s+|massive\\s+)?(fan|supporter|admirer)\\s+of\\b/i",
                    "/\\bi\\s+(never|rarely|seldom|completely\\s+avoid|refuse\\s+to|strongly\\s+dislike)\\s+(use|do|touch|install|work\\s+with|try|buy|eat)\\b/i",
                    "/\\bmy\\s+(least\\s+favorite|least\\s+favourite|absolute\\s+worst)\\b/i",

                    // 2. Casual, Annoyed, and Loose Negative Expressions (Chill, Casual, Child)
                    "/\\b(i['’]m\\s+not\\s+really\\s+into|i['’]m\\s+not\\s+keen\\s+on|i['’]m\\s+sick\\s+of|i['’]m\\s+tired\\s+of)\\b/i",
                    "/\\b(that['’]s\\s+not\\s+my\\s+thing|not\\s+my\\s+jam|count\\s+me\\s+out\\s+for|i['’]m\\s+good\\s+without)\\b/i",
                    "/\\bi\\s+(pass\\s+on|steer\\s+clear\\s+of|run\\s+away\\s+from|stay\\s+away\\s+from)\\b/i",
                    "/\\b(gives\\s+me\\s+anxiety|drives\\s+me\\s+crazy|annoys\\s+me)\\b/i",

                    // 3. Blocklists, Restrictions, & Anti-Profiles (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(dislikes|blocklist|blacklist|aversions)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings)\\s*(dislikes|hates|avoid|blacklist|blocklist)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'preference.favourite',
                'type'        => 'preference',
                'group'       => 'preference',
                'priority'    => 82,
                'weight'      => 65,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'PREFERENCE_MATCH',
                'description' => 'User declares a favourite or preferred thing',
                'patterns'    => [
                    // 1. Direct Absolute Favorites & Primary Defaults (Sophisticated, Regular, Professional)
                    "/\\bmy\\s+(favourite|favorite|preferred|go-to|main|default|primary|top-tier|ideal)\\s+([a-z\\s_-]+)\\s*(is|:|\\bstands\\s+as\\b)\\b/i",
                    "/\\bmy\\s+(absolute\\s+)?(favourite|favorite)\\b/i",
                    "/\\bmy\\s+(preference|choice)\\s+(is|falls\\s+on|leans\\s+towards)\\b/i",
                    "/\\bi\\s+(strongly\\s+)?prefer\\b/i",

                    // 2. Casual, Enthusiastic, and Loose Cues (Chill, Casual, Child)
                    "/\\b(hands\\s+down|by\\s+far|without\\s+a\\s+doubt)[,\\s]+my\\s+(fav|fave|favorite|favourite)\\b/i",
                    "/\\b(that['’]s|this\\s+is)\\s+my\\s+(number\\s+one|top\\s+pick|go-to)\\b/i",
                    "/\\bif\\s+i\\s+had\\s+to\\s+choose[,\\s]+i['’]d\\s+(always\\s+)?(pick|go\\s+with|select)\\b/i",
                    "/\\bi['’]m\\s+partial\\s+to\\b/i",

                    // 3. System Defaults & Explicit Form Layouts (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(default|primary\\s+choice|favourite|favorite)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings)\\s*(favourite|favorite|fav|default|preferred)[:\\s-]/i"
                ],
            ],


            // ── CONSTRAINT / RULE PATTERNS (priority 85–89) ───────────────────

            [
                'id'          => 'constraint.response',
                'type'        => 'rule',
                'group'       => 'constraint',
                'priority'    => 85,
                'weight'      => 75,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'CONSTRAINT_MATCH',
                'description' => 'User sets a response constraint or formatting rule',
                'patterns'    => [
                    // 1. Direct Formatting & Stylistic Directives (Sophisticated, Regular, Professional)
                    "/\\b(always|never)\\s+(use|write|respond|answer|include|avoid|add|give|format|show|explain|start|end|output|render|print)\\b/i",
                    "/\\b(don['’]t|do\\s+not|never)\\s+(include|add|show|explain|use|mention|start|output|print|bring\\s+up|provide)\\b/i",
                    "/\\b(please\\s+)?(use|utilize|apply|stick\\s+to)\\s+(tabs|spaces|markdown|plain\\s+text|bullet\\s+points|numbered\\s+lists|json|latex|code\\s+blocks|table\\s+format)\\b/i",
                    "/\\b(format\\s+your\\s+)?(response[s]?|answer[s]?|output[s]?)\\s+(as|using|in|with)\\b/i",

                    // 2. Length, Depth, & Brevity Constraints (Chill, Casual, Every User)
                    "/\\b(keep\\s+(your\\s+)?(responses?|answers?|replies?)|make\\s+it|be)\\s+(short|brief|concise|detailed|thorough|straight\\s+to\\s+the\\s+point|snappy|elaborate)\\b/i",
                    "/\\brespond\\s+(briefly|concisely|in\\s+detail|in\\s+depth|without\\s+fluff|with\\s+just\\s+the\\s+code)\\b/i",
                    "/\\b(no\\s+yapping|cut\\s+the\\s+chatter|just\\s+give\\s+me|give\\s+only)\\b/i",
                    "/\\bin\\s+(\\d+|one|two|three|four|five)\\s+(sentences?|words?|paragraphs?|bullet\\s+points?)\\b/i",

                    // 3. System Systemic Prompting & Override Tokens (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(constraints|formatting|rules|style)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|system)\\s*(rules|constraints|format|style|length|mode)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'constraint.dont_want',
                'type'        => 'rule',
                'group'       => 'constraint',
                'priority'    => 86,
                'weight'      => 60,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'CONSTRAINT_MATCH',
                'description' => 'User states something they do not want',
                'patterns'    => [
                    // 1. Direct Negative Requests & Structural Exclusions (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(don['’]t|do\\s+not)\\s+(want|need|require|care\\s+for)\\b/i",
                    "/\\b(please\\s+)?(don['’]t|do\\s+not)\\s+(include|add|show|explain|use|mention|start|print|output|display)\\b/i",
                    "/\\b(stop|cease|refrain\\s+from)\\s+(using|including|adding|doing|saying|generating|outputting|printing|yapping)\\b/i",
                    "/\\bi\\s+want\\s+to\\s+(exclude|omit|bypass|skip|remove|delete)\\b/i",

                    // 2. Casual, Conversational, or Blunt Rejections (Chill, Casual, Child)
                    "/\\b(i['’]m\\s+not\\s+interested\\s+in|i\\s+can\\s+do\\s+without|no\\s+need\\s+for|skip\\s+the)\\b/i",
                    "/\\b(leave\\s+out|throw\\s+out|get\\s+rid\\s+of|cut\\s+out)\\b/i",
                    "/\\b(i\\s+don['’]t\\s+give\\s+a\\s+sh|i\\s+don['’]t\\s+care\\s+about)\\b/i",
                    "/\\b(no\\s+more|disable|turn\\s+off)\\b/i",

                    // 3. System Restrictions, Negative Prompts, & Filters (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(exclusions|negative\\s+constraints|restrictions)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|system)\\s*(exclude|omit|disabled|blacklist|drop|negative)[:\\s-]/i"
                ],
            ],

            // ── SKILL RULES (priority 100–109) ────────────────────────────────

            [
                'id'          => 'skill.general',
                'type'        => 'skill',
                'group'       => 'skill',
                'priority'    => 100,
                'weight'      => 60,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'SKILL_MATCH',
                'description' => 'User declares knowledge or ability',
                'patterns'    => [
                    // 1. Direct Skill & Competency Declarations (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(know|understand|comprehend|am\\s+familiar\\s+with|have\\s+experience\\s+(in|with)|have\\s+a\\s+background\\s+in)\\b/i",
                    "/\\bi\\s+(can|am\\s+able\\s+to|know\\s+how\\s+to|excel\\s+at|mastered)\\b/i",
                    "/\\bi['’]m\\s+(proficient|experienced|skilled|an\\s+expert|certified|versed|adept)\\s+(in|with|at)\\b/i",
                    "/\\bi\\s+(specialize|specialise)(\\s+in)?\\b/i",

                    // 2. Casual, Fluid, or Everyday Capacity Phrases (Chill, Casual, Every User)
                    "/\\bi['’]m\\s+(really\\s+)?(good\\s+at|decent\\s+at|into|picking\\s+up|learning|practicing)\\b/i",
                    "/\\bi\\s+(frequent|dabbled\\s+in|messed\\s+around\\s+with|know\\s+my\\s+way\\s+around)\\b/i",
                    "/\\b(my\\s+skills\\s+include|my\\s+expertise\\s+is|i['’]m\\s+a\\s+pro\\s+at)\\b/i",

                    // 3. Technical Resume, Capability, & Schema Definitions (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(skills|stack|capabilities|languages)\\s+to\\b/i",
                    "/\\b(profile|user|meta|cv|resume)\\s*(skills|expertise|stack|capabilities|competencies)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'skill.built',
                'type'        => 'skill',
                'group'       => 'skill',
                'priority'    => 101,
                'weight'      => 65,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'SKILL_MATCH',
                'description' => 'User declares something they have built or created',
                'patterns'    => [
                    // 1. Direct Creation, Engineering, and Launch Actions (Coder, Professional, Creator)
                    "/\\bi\\s+(built|created|developed|wrote|made|designed|architected|shipped|launched|deployed|maintain|own|run|authored|engineered|coded|implemented|configured)\\b/i",
                    "/\\bi['’]ve\\s+(built|created|developed|written|made|shipped|launched|deployed|engineered|coded|implemented|designed|architected)\\b/i",
                    "/\\bi\\s+have\\s+(built|created|developed|written|made|shipped|launched|deployed|engineered|coded)\\b/i",
                    "/\\bmy\\s+(latest\\s+|own\\s+)?(project|app|application|software|creation|startup|tool|library|package|plugin|extension|product|saas)\\s*(is|:|\\bgot\\s+launched\\b)\\b/i",

                    // 2. Casual, Creative, or Hobbyist Development Phrases (Chill, Casual, Child)
                    "/\\bi\\s+(put\\s+together|whipped\\s+up|hacked\\s+together|spun\\s+up|came\\s+up\\s+with)\\b/i",
                    "/\\bi['’]m\\s+the\\s+(creator|author|developer|founder|brain[s]?|mind)\\s+(behind|of)\\b/i",
                    "/\\bhere['’]s\\s+a\\s+(cool\\s+)?(thing|script|program|site)\\s+i\\s+(made|did|put\\s+together)\\b/i",

                    // 3. Portfolio, Repository, and Version Control Declarations (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(portfolio|projects|products|creations)\\s+to\\b/i",
                    "/\\b(profile|user|meta|cv|resume|github)\\s*(built|created|projects|portfolio|products|repos)[:\\s-]/i"
                ],
            ],


            // ── HABIT RULES (priority 105–109, feature-flagged) ───────────────

            [
                'id'          => 'habit.general',
                'type'        => 'preference',
                'group'       => 'habit',
                'priority'    => 105,
                'weight'      => 55,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'HABIT_MATCH',
                'description' => 'User describes a recurring habit or pattern',
                'patterns'    => [
                    // 1. Explicit Behavioral Frequencies & Routines (Sophisticated, Regular, Professional)
                    "/\\bi\\s+(usually|typically|normally|generally|often|frequently|regularly|consistently|routinely|habitually)\\b/i",
                    "/\\bi\\s+(have\\s+a\\s+habit\\s+of|make\\s+it\\s+a\\s+point\\s+to|tend\\s+to|am\\s+used\\s+to)\\b/i",
                    "/\\bmy\\s+(usual\\s+)?(routine|habit|pattern|schedule|practice|workflow)\\s*(is|:|\\bconsists\\s+of\\b)\\b/i",

                    // 2. Casual Absolute/Rare Habits & Quirks (Chill, Casual, Child)
                    "/\\bi\\s+(almost\\s+)?(always|never)\\s+(start|begin|end|use|do|write|check|open|run|go|cook)\\b/i",
                    "/\\bi\\s+(rarely|seldom|scarcely|hardly\\s+ever)\\s+(use|do|go|work|touch|visit|click)\\b/i",
                    "/\\b(every\\s+day|day\\s+in\\s+and\\s+day\\s+out|more\\s+often\\s+than\\s+not|as\\s+a\\s+rule)\\b/i",
                    "/\\bi['’]m\\s+in\\s+the\\s+habit\\s+of\\b/i",

                    // 3. System Scheduling, Personal Settings, & Logging (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(habits|routines|frequency|intervals)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|tracker)\\s*(habit|routine|pattern|frequency|cadence)[:\\s-]/i"
                ],
            ],


            // ── GOAL RULES (priority 110–114, feature-flagged) ────────────────

            [
                'id'          => 'goal.general',
                'type'        => 'preference',
                'group'       => 'goal',
                'priority'    => 110,
                'weight'      => 55,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'GOAL_MATCH',
                'description' => 'User expresses a goal or aspiration',
                'patterns'    => [
                    // 1. Direct Strategic Goals & Professional Objectives (Sophisticated, Regular, Professional)
                    "/\\bmy\\s+(current\\s+|long-term\\s+|primary\\s+)?(goal|objective|aim|target|plan|ambition|dream|mission|vision|milestone)\\s*(is|:|\\bstands\\s+at\\b)\\b/i",
                    "/\\bi['’]m\\s+(actively\\s+)?(trying|planning|working\\s+on|aiming|striving|endeavoring|intending)\\s+to\\b/i",
                    "/\\bi\\s+(want|hope|wish|intend|plan|aspire)\\s+to\\b/i",
                    "/\\bmy\\s+(next|ultimate)\\s+(step|goal|project|move|phase|endeavor)\\s*(is|:|\\bwill\\s+be\\b)\\b/i",

                    // 2. Casual, Loose, & Passion-Driven Aspirations (Chill, Casual, Child)
                    "/\\b(i['’]ve\\s+been\\s+meaning\\s+to|i['’]m\\s+looking\\s+to|i\\s+wanna|i['’]d\\s+love\\s+to)\\b/i",
                    "/\\b(someday\\s+i\\s+will|one\\s+day\\s+i\\s+want|my\\s+big\\s+dream\\s+is)\\b/i",
                    "/\\bi\\s+have\\s+my\\s+(eyes|sights)\\s+set\\s+on\\b/i",
                    "/\\b(saving\\s+up\\s+for|building\\s+towards|chasing)\\b/i",

                    // 3. Roadmap Trackers, Tasks, & OKR Systems (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(goals|objectives|targets|roadmap)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|okr|roadmap)\\s*(goals|objective|target|plan|ambition)[:\\s-]/i"
                ],
            ],


            // ── TOOL RULES (priority 120–129, feature-flagged) ────────────────

            [
                'id'          => 'tool.framework',
                'type'        => 'preference',
                'group'       => 'tool',
                'priority'    => 120,
                'weight'      => 60,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'TOOL_FRAMEWORK_MATCH',
                'description' => 'User declares a framework preference',
                'patterns'    => [
                    // 1. Direct Framework & Ecosystem Alignments (Coder, Professional, Power User)
                    "/\\bi\\s+(use|prefer|work\\s+with|specialize\\s+in|build\\s+(with|in|using))\\s+(laravel|django|rails|express|fastapi|next\\.?js|nuxt|vue|react|angular|svelte|symfony|spring|flask|gin|fiber|asp\\.?net|phoenix|remix|astro|solid|tailwind)\\b/i",
                    "/\\bmy\\s+(current\\s+)?(framework|stack|tech\\s+stack|tooling|architecture|backend|frontend)\\s+(of\\s+choice|is|preference|defaults\\s+to)\\b/i",
                    "/\\bi['’]ve\\s+(switched|migrated|moved|transitioned)\\s+to\\s+(laravel|django|rails|react|vue|next|svelte|angular)\\b/i",

                    // 2. Casual, Stack-Specific, or Project-Based Declarations (Chill, Casual, Coder)
                    "/\\bi['’]m\\s+(spinning\\s+up|building|writing|developing)\\s+(an?\\s+app\\s+in|a\\s+project\\s+with|everything\\s+on)\\s+(laravel|django|rails|next|react|vue|nuxt|svelte)\\b/i",
                    "/\\bi\\s+swear\\s+by\\s+(laravel|django|rails|next\\.?js|react|vue|tailwind)\\b/i",
                    "/\\b(our\\s+codebase|my\\s+project)\\s+is\\s+(running|built|written)\\s+(on|in|using)\\s+(laravel|django|rails|next|react|vue)\\b/i",

                    // 3. Environment Configs, Meta Headers, & App Initializers (Coder, System)
                    "/\\b(set|change|update)\\s+(my\\s+)?(framework|stack|preset|boilerplate)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|env|manifest)\\s*(framework|stack|tech|engine|preset)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'tool.editor',
                'type'        => 'preference',
                'group'       => 'tool',
                'priority'    => 121,
                'weight'      => 55,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'TOOL_EDITOR_MATCH',
                'description' => 'User declares an editor or IDE preference',
                'patterns'    => [
                    // 1. Direct IDE & Text Editor Selection (Coder, Professional, Power User)
                    "/\\bi\\s+(use|prefer|work\\s+in|develop\\s+in|code\\s+in|write\\s+code\\s+in)\\s+(vscode|vs\\s+code|visual\\s+studio\\s+code|vim|neovim|nvim|emacs|phpstorm|intellij|pycharm|webstorm|goland|rider|xcode|android\\s+studio|sublime|cursor|zed|clion|rubymine)\\b/i",
                    "/\\bmy\\s+(current\\s+)?(editor|ide|development\\s+environment|code\\s+editor)\\s*(is|:|\\bdefaults\\s+to|of\\s+choice\\s+is\\b)\\b/i",
                    "/\\bi['’]ve\\s+(switched|migrated|moved|transitioned)\\s+to\\s+(vscode|vs\\s+code|vim|neovim|nvim|phpstorm|cursor|zed|emacs)\\b/i",

                    // 2. Casual, Extension, or Environment-Based Configurations (Chill, Casual, Coder)
                    "/\\bi['’]m\\s+(building|coding|working)\\s+(inside|in|on)\\s+(vscode|phpstorm|cursor|zed|vim|neovim)\\b/i",
                    "/\\bi\\s+swear\\s+by\\s+(vscode|vim|neovim|phpstorm|cursor|zed)\\b/i",
                    "/\\b(open\\s+this|i\\s+have\\s+this\\s+open)\\s+in\\s+(vscode|phpstorm|cursor|zed|sublime)\\b/i",

                    // 3. Editor Configs, Dotfiles, & Core Extension Mappings (Coder, System)
                    "/\\b(set|change|update)\\s+(my\\s+)?(editor|ide|keybindings)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|env|dotfiles)\\s*(editor|ide|workspace|theme)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'tool.database',
                'type'        => 'preference',
                'group'       => 'tool',
                'priority'    => 122,
                'weight'      => 60,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'TOOL_DB_MATCH',
                'description' => 'User declares a database preference',
                'patterns'    => [
                    // 1. Direct Database Engine & Infrastructure Selection (Coder, Professional, Power User)
                    "/\\bi\\s+(use|prefer|work\\s+with|run|deploy|manage)\\s+(postgresql|postgres|mysql|mariadb|sqlite|mongodb|redis|cassandra|dynamodb|cockroachdb|planetscale|supabase|neon|firestore|clickhouse|oracle|mssql|sql\\s+server)\\b/i",
                    "/\\bmy\\s+(current\\s+)?(database|db|datastore|storage\\s+layer|db\\s+engine)\\s*(is|:|\\bdefaults\\s+to|of\\s+choice\\s+is\\b)\\b/i",
                    "/\\bi['’]ve\\s+(switched|migrated|moved|transitioned)\\s+to\\s+(postgresql|postgres|mysql|mongodb|sqlite|supabase|neon)\\b/i",

                    // 2. Casual, Query, or Connection-Based Declarations (Chill, Casual, Coder)
                    "/\\bi['’]m\\s+(spinning\\s+up|setting\\s+up|connecting\\s+to|querying|running)\\s+(an?\\s+instance\\s+of|a|some)\\s+(postgres|postgresql|mysql|sqlite|mongodb|redis)\\b/i",
                    "/\\bi\\s+swear\\s+by\\s+(postgres|postgresql|mysql|sqlite|supabase)\\b/i",
                    "/\\b(our\\s+production|my\\s+local)\\s+(db|database)\\s+is\\s+(running|hosted|built)\\s+(on|in|using)\\s+(postgres|postgresql|mysql|sqlite|mongodb|redis)\\b/i",

                    // 3. Environment Configs, Connection Strings, & Schema Schema Keys (Coder, System)
                    "/\\b(set|change|update)\\s+(my\\s+)?(database|db|db_connection|driver)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|env|schema)\\s*(database|db|driver|engine|connection)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'tool.language',
                'type'        => 'preference',
                'group'       => 'tool',
                'priority'    => 123,
                'weight'      => 60,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'TOOL_LANG_MATCH',
                'description' => 'User declares a programming language preference',
                'patterns'    => [
                    // 1. Direct Language Selection & Professional Workflows (Coder, Professional, Power User)
                    "/\\bi\\s+(primarily\\s+|mainly\\s+|mostly\\s+)?(code|program|write|develop|work)\\s+(in|with|using)\\s+(php|python|javascript|js|typescript|ts|golang?|go|rust|java|kotlin|swift|c#|c\\+\\+|cpp|ruby|elixir|scala|haskell|clojure|dart|zig)\\b/i",
                    "/\\bmy\\s+(current\\s+|primary\\s+|main\\s+|preferred\\s+)?(language|programming\\s+language)\\s*(is|:|\\bdefaults\\s+to|of\\s+choice\\s+is\\b)\\b/i",
                    "/\\bi['’]ve\\s+(switched|migrated|moved|transitioned)\\s+to\\s+(typescript|ts|rust|golang?|go|kotlin|python|php)\\b/i",

                    // 2. Casual, Fluid, or Project-Specific Stack Declarations (Chill, Casual, Coder)
                    "/\\bi['’]m\\s+(writing|building|developing|coding)\\s+(everything|my\\s+backend|my\\s+frontend|this)\\s+(in|with|using)\\s+(php|python|js|typescript|ts|go|golang|rust|ruby)\\b/i",
                    "/\\bi\\s+swear\\s+by\\s+(typescript|ts|rust|go|golang|python|php)\\b/i",
                    "/\\b(our\\s+codebase|my\\s+project)\\s+is\\s+(written|built)\\s+(in|using)\\s+(php|python|javascript|typescript|go|rust)\\b/i",

                    // 3. Environment Specs, File Configurations, & Compiler Directives (Coder, System)
                    "/\\b(set|change|update)\\s+(my\\s+)?(language|runtime|compiler|target)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|env|manifest)\\s*(language|lang|runtime|engine)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'tool.os',
                'type'        => 'preference',
                'group'       => 'tool',
                'priority'    => 124,
                'weight'      => 50,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'TOOL_OS_MATCH',
                'description' => 'User declares an OS preference',
                'patterns'    => [
                    // 1. Direct OS Selection & Development Environments (Coder, Professional, Power User)
                    "/\\bi\\s+(use|run|work\\s+on|develop\\s+on|code\\s+on)\\s+(macos|mac\\s+os|osx|linux|ubuntu|debian|fedora|arch\\s+linux|windows|wsl|wsl2|pop_os|mint)\\b/i",
                    "/\\bmy\\s+(current\\s+)?(os|operating\\s+system|machine|laptop|computer|rig|pc|box)\\s*(is|:|runs?|\\bdefaults\\s+to\\b)\\b/i",
                    "/\\bi['’]ve\\s+(switched|migrated|moved|transitioned)\\s+to\\s+(macos|mac|linux|ubuntu|arch|windows|wsl)\\b/i",

                    // 2. Casual, Machine Setup, or Environmental Context (Chill, Casual, Every User)
                    "/\\bi['’]m\\s+(building|working|coding)\\s+on\\s+(an?\\s+)?(mac|macbook|linux\\s+box|windows\\s+machine|ubuntu\\s+server)\\b/i",
                    "/\\bi\\s+swear\\s+by\\s+(macos|linux|arch)\\b/i",
                    "/\\b(on\\s+my\\s+mac|on\\s+my\\s+windows|on\\s+my\\s+linux|inside\\s+wsl)\\b/i",

                    // 3. System Environment Specs & Cross-Platform Targeting (Coder, System)
                    "/\\b(set|change|update)\\s+(my\\s+)?(os|operating\\s+system|platform|target_os)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|env|sysinfo)\\s*(os|operating_system|platform|kernel)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'tool.package_manager',
                'type'        => 'preference',
                'group'       => 'tool',
                'priority'    => 125,
                'weight'      => 50,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'TOOL_PKG_MATCH',
                'description' => 'User declares a package manager preference',
                'patterns'    => [
                    // 1. Direct Dependency & Package Manager Selection (Coder, Professional, Power User)
                    "/\\bi\\s+(use|prefer|run|install\\s+with|manage\\s+with)\\s+(npm|yarn|pnpm|bun|composer|pip|pipenv|poetry|cargo|go\\s+modules?|gem|bundler|brew|apt|pacman|dnf|nix)\\b/i",
                    "/\\bmy\\s+(current\\s+|preferred\\s+)?(package\\s+manager|dependency\\s+manager|installer)\\s*(is|:|\\bdefaults\\s+to\\b)\\b/i",
                    "/\\bi['’]ve\\s+(switched|migrated|moved|transitioned)\\s+to\\s+(pnpm|bun|yarn|composer|cargo|poetry)\\b/i",

                    // 2. Casual Execution, Script Running, or Ecosystem Commands (Chill, Casual, Coder)
                    "/\\bi['’]m\\s+(running|installing|initializing|updating)\\s+(via|with|using)\\s+(npm|pnpm|bun|yarn|composer|cargo|brew)\\b/i",
                    "/\\bi\\s+swear\\s+by\\s+(pnpm|bun|cargo|poetry|brew)\\b/i",
                    "/\\b(just\\s+)?(npm\\s+install|pnpm\\s+add|bun\\s+add|composer\\s+require|cargo\\s+add)\\b/i",

                    // 3. System Manifests, Environment Lockfiles, & Lock Configurations (Coder, System)
                    "/\\b(set|change|update)\\s+(my\\s+)?(package\\s+manager|dependency\\s+tool|installer)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|env|manifest)\\s*(package_manager|pkg_mgr|installer|lockfile)[:\\s-]/i"
                ],
            ],


            // ── COMMUNICATION RULES (priority 130–139, feature-flagged) ──────

            [
                'id'          => 'communication.format',
                'type'        => 'rule',
                'group'       => 'communication',
                'priority'    => 130,
                'weight'      => 55,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'COMMUNICATION_MATCH',
                'description' => 'User declares formatting or communication preferences',
                'patterns'    => [
                    // 1. Direct Formatting & Structural Specifications (Sophisticated, Regular, Professional)
                    "/\\b(always\\s+)?(use|format|write|respond|output|render|display)\\s+(in\\s+)?(markdown|plain\\s+text|bullet\\s+points|numbered\\s+lists?|code\\s+blocks?|tables?|json|latex)\\b/i",
                    "/\\buse\\s+(tabs|spaces|\\d+\\s+spaces)\\s+for\\s+(indentation|indenting|tabs)\\b/i",
                    "/\\bi\\s+(prefer|require|expect)\\s+(detailed|brief|concise|short|long|thorough|granular)\\s+(answers?|responses?|explanations?|outputs?)\\b/i",
                    "/\\brespond\\s+(in|with|using)\\s+(english|code|examples?|bullet\\s+points?|diagrams?|snippets?)\\b/i",

                    // 2. Casual, Persona-Driven, or Conversational Cues (Chill, Casual, Every User)
                    "/\\b(give\\s+it\\s+to\\s+me\\s+in|hit\\s+me\\s+with|break\\s+it\\s+down\\s+in|wrap\\s+it\\s+in)\\s+(bullets|code|plain\\s+text|markdown)\\b/i",
                    "/\\b(talk|speak|write|reply)\\s+to\\s+me\\s+(like\\s+i['’]m|in|with|using)\\b/i",
                    "/\\b(skip|cut|drop)\\s+the\\s+(intro|outro|fluff|yapping|explanation|commentary)\\b/i",
                    "/\\b(just\\s+the\\s+code|code\\s+only|no\\s+prose)\\b/i",

                    // 3. Client System Frameworks & Layout Meta Targets (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(communication|formatting|rendering|markup)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|system)\\s*(communication|format|markup|indent|verbosity)[:\\s-]/i"
                ],
            ],

            [
                'id'          => 'communication.style',
                'type'        => 'rule',
                'group'       => 'communication',
                'priority'    => 131,
                'weight'      => 55,
                'terminal'    => false,
                'enabled'     => true,
                'volatility'  => 'persistent',
                'reason_code' => 'COMMUNICATION_MATCH',
                'description' => 'User declares response style preferences',
                'patterns'    => [
                    // 1. Direct Style Requirements & Technical Brevity (Sophisticated, Regular, Professional)
                    "/\\b(don['’]t|do\\s+not)\\s+(explain|over.?explain|repeat|pad|add\\s+filler|lecture|sugarcoat)\\b/i",
                    "/\\bget\\s+(straight\\s+)?to\\s+the\\s+point\\b/i",
                    "/\\b(skip|cut|omit)\\s+the\\s+(intro|outro|preamble|explanation|pleasantries|greetings|chatter)\\b/i",
                    "/\\bi\\s+(like|want|prefer|need|appreciate)\\s+(direct|straight|honest|blunt|no-nonsense|raw)\\s+(answers?|responses?|feedback|delivery)\\b/i",
                    "/\\bno\\s+(fluff|filler|padding|unnecessary\\s+text|yapping|commentary)\\b/i",

                    // 2. Casual, Persona-Driven, or Emotional Demands (Chill, Casual, Every User)
                    "/\\b(stop\\s+being\\s+so\\s+robot|talk\\s+like\\s+a\\s+human|be\\s+real|give\\s+it\\s+to\\s+me\\s+straight)\\b/i",
                    "/\\b(cut\\s+the\\s+crap|less\\s+talk|more\\s+action|just\\s+the\\s+facts)\\b/i",
                    "/\\b(don['’]t\\s+waste\\s+my\\s+time|keep\\s+it\\s+real|don['’]t\\s+bore\\s+me)\\b/i",
                    "/\\b(without\\s+any\\s+extra\\s+words|straight\\s+shooter)\\b/i",

                    // 3. Systemic Directives, Persona Keys, & Overrides (Coder, Power User)
                    "/\\b(set|change|update)\\s+(my\\s+)?(tone|style|persona|communication_style)\\s+to\\b/i",
                    "/\\b(profile|user|meta|config|settings|system)\\s*(style|tone|persona|verbosity|attitude)[:\\s-]/i"
                ],
            ],

        ],

        // Future locales — zero cost placeholders
        'bn' => [],
        'hi' => [],

    ], 

];