<?php

namespace App\Services\Memory;

class CodeDetectionService
{
    private function containsFencedCodeBlock(string $text): bool
    {
        return preg_match('/(?:^|\n)(```|~~~)[ \t]*[a-zA-Z0-9_+-]*[^\r\n]*\n[\s\S]*?\n\1[ \t]*(?=\n|$)/u', $text) === 1;
    }
    /**
     * Determines if the given text is primarily code, a command, or code-heavy.
     * Uses a scoring system based on various heuristics and category tracking.
     */
    public function isCodeHeavy(string $text): bool
    {
        // 1. Fenced code blocks = code immediately
        if ($this->containsFencedCodeBlock($text)) {
            return true;
        }

        [$score, $categories] = $this->calculateScoreAndCategories($text);
        
        // Prose penalty: if it lacks typical code/command structures, penalize the score heavily.
        // This prevents coincidental English words (like "for", "else", "this") from triggering code detection.
        $hasStructuralCode = in_array('code_operators', $categories) || 
                             in_array('syntax_density', $categories) || 
                             in_array('inline_code', $categories) || 
                             in_array('fenced_code', $categories) ||
                             in_array('cli_command', $categories) ||
                             in_array('file_extensions', $categories);
                             
        if (!$hasStructuralCode) {
            $score -= 20; 
        }

        return $score >= 14 && count($categories) >= 2;
    }

    /**
     * Returns an array containing the total score and an array of triggered categories.
     *
     * @return array{0: int, 1: array<string>}
     */
    public function calculateScoreAndCategories(string $text): array
    {
        $score = 0;
        $categories = [];
        $lowerText = mb_strtolower($text, 'UTF-8');

        // Note: Fenced code blocks are handled early in isCodeHeavy(), but we keep the logic 
        // here just in case calculateScoreAndCategories is used independently.
        if (preg_match('/(?:^|\n)(```|~~~)[ \t]*[a-zA-Z0-9_+-]*[^\r\n]*\n[\s\S]*?\n\1[ \t]*(?=\n|$)/u', $text)) {
            $score += 20; 
            $categories['fenced_code'] = true;
        }

        // 2. Inline code (Moderate positive signal)
        if (preg_match_all('/`[^`]+`/u', $text, $matches)) {
            $score += count($matches[0]) * 3;
            $categories['inline_code'] = true;
        }
        
        // Dense inline backtick usage (fallback)
        if (substr_count($text, '`') >= 4) {
            $score += 5;
            $categories['inline_code'] = true;
        }

        // 3. CLI / shell / terminal commands
        $cliPatterns = [
            // UNIX/Linux/macOS & popular developer CLI starters
            '/^(?:php\s+artisan|composer\s+|npm\s+|yarn\s+|pnpm\s+|npx\s+|git\s+|docker(?:-compose)?\s+|kubectl\s+|curl\s+|wget\s+|phpunit\s+|pytest\s+|node\s+|python3?\s+|pip3?\s+|pipenv\s+|poetry\s+|bundle\s+|rails\s+|rake\s+|irb\s+|gradle\s+|mvn\s+|make\s+|cmake\s+|cargo\s+|go\s+|deno\s+|dotnet\s+|java\s+|javac\s+|rustc\s+|tsc\s+|eslint\s+|prettier\s+|aws\s+|gcloud\s+|az\s+|firebase\s+|heroku\s+|vagrant\s+|ansible\s+|helm\s+|terraform\s+|cf\s+|sam\s+|flutter\s+|react-native\s+|nunit\s+|msbuild\s+|sqlcmd\s+|mongo\s+|psql\s+|mysql\s+|psql\s+|sqlite3\s+|plink\s+|scp\s+|sftp\s+|ftp\s+|expect\s+|pwsh\s+|powershell\s+|bash\s+-c\s+|sh\s+-c\s+|zsh\s+-c\s+)/um',

            // System, scripting, and admin utilities plus Windows and *nix system commands
            '/^(?:sudo\s+|apt(-get)?\s+|brew\s+|yum\s+|dnf\s+|zypper\s+|pacman\s+|apk\s+|snap\s+|systemctl\s+|service\s+|chmod\s+|chown\s+|ls(?:\s+-)?|dir\s+|cd\s+(?:[a-zA-Z]:)?[\/\\\\]?|cp\s+-|mv\s+|rm\s+-|del\s+|erase\s+|rmdir\s+|mkdir\s+|mklink\s+|wmic\s+|schtasks\s+|reg\s+|icacls\s+|net\s+|tasklist\s+|taskkill\s+|attrib\s+|echo\s+|set\s+|export\s+|unset\s+|env\s+|printenv\s+|hostname\s+|ps\s+|kill\s+|htop\s+|top\s+|df\s+|du\s+|ifconfig\s+|ip\s+|route\s+|hostname\s+|tracert\s+|traceroute\s+|ping\s+|arp\s+|nslookup\s+|dig\s+|netstat\s+|whoami\s+|id\s+|passwd\s+|useradd\s+|usermod\s+|userdel\s+|passwd\s+|history\s+|clear\s+|cls\s+)/um',

            // Windows Powershell and CMD 
            '/^(?:PS\s*[A-Za-z]:[\\/][^>]*>\s+|[A-Za-z]:[\\/]?.*)/um',

            // Prompt indicators for common shells and scripting tools 
            '/^\s*[\$\#]\s+/um',          // bash/zsh/fish root prompt or normal prompt
            '/^\s*>\s+/um',               // Windows CMD or SQL/REPL shell
            '/^\s*::\s?.*/um',            // Windows CMD comments
            '/^>>>[ \t]+.*/um',           // Python REPL
            '/^\.\.\.[ \t]+.*/um',        // Python REPL indented block
            '/^\s*PS\s+.*>/um',           // PowerShell prompt (generic)
        ];
        
        foreach ($cliPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $score += count($matches[0]) * 8;
                $categories['cli_command'] = true;
            }
        }

        // 4. SQL
        $sqlKeywords = [
            'select ', 'insert into ', 'insert ', 'update ', 'delete from ', 'delete ', 'create table ', 'create view ', 'create index ', 'alter table ', 'alter view ', 'drop table ', 'drop view ', 'drop index ',
            'truncate table ', 'merge into ', 'replace into ', 'create database ', 'drop database ', 'use ', 'set ', 'grant ', 'revoke ', 'commit', 'rollback', 'begin', 'start transaction', 'savepoint',
            'release savepoint', 'show tables', 'show databases', 'describe ', 'desc ', 'explain ', 'call ', 'declare ', 'end', 'if ', 'then', 'else', 'elsif', 'loop', 'repeat', 'until', 'case ', 'when ', 'default ',
            'cursor ', 'fetch ', 'open ', 'close ', 'prepare ', 'execute ', 'deallocate ', 'with ', 'over(', 'partition by', 'having ', 'limit ', 'offset '
        ];
        $sqlScore = 0;
        foreach ($sqlKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                $sqlScore += 2;
            }
        }
        if ($sqlScore > 0) {
            if (preg_match('/\b(?:select|insert(?:\s+into)?|update|delete(?:\s+from)?|create(?:\s+(?:table|view|index|database))?|alter(?:\s+(?:table|view))?|drop(?:\s+(?:table|view|index|database))?|truncate\s+table|merge(?:\s+into)?|replace(?:\s+into)?|commit|rollback|begin|start\s+transaction|savepoint|release\s+savepoint|grant|revoke|set|use|show(?:\s+(?:tables|databases))?|describe|desc|explain|call|declare|end|if|then|else|elsif|loop|repeat|until|case|when|default|cursor|fetch|open|close|prepare|execute|deallocate|with|over|partition\s+by|having|limit|offset)\b/is', $text)) {
                $score += 10;
                $categories['sql'] = true;
            }
            $score += $sqlScore;
            $categories['sql'] = true;
        }

        // 5. Language keywords
        $keywords = [
            // Common to many languages
            'function', 'class', 'interface', 'namespace', 'enum', 'struct', 'union', 'typedef', 'record', 'trait', 'object',
            'public', 'private', 'protected', 'abstract', 'final', 'static', 'const', 'let', 'var', 'mutable', 'readonly',
            'extends', 'implements', 'with', 'from', 'import', 'export', 'package', 'module', 'as', 'yield', 'include', 'require', 
            'throws', 'throw', 'catch', 'try', 'finally', 'except', 'raise',
            // Control flow
            'if', 'else', 'elif', 'elseif', 'for', 'foreach', 'while', 'do', 'switch', 'case', 'default', 'break', 'continue', 'goto', 'when', 'until', 'repeat',
            // Functions/methods/procs
            'async', 'await', 'return', 'yield', 'def', 'lambda', 'arrow', 'fn', 'func', 'procedure', 'sub', 'macro', 'inline',
            // OOP/references
            'self', 'this', 'super', 'base', 'new', 'delete', 'operator', 'override', 'virtual', 'friend', 'implements',
            // Types and null checks
            'type', 'typeof', 'instanceof', 'is', 'as', 'nullptr', 'null', 'undefined', 'NaN', 'void', 'any', 'never', 'in', 'out',
            'bool', 'boolean', 'int', 'integer', 'float', 'double', 'real', 'char', 'string', 'str', 'long', 'short', 'byte', 'bytes', 'array', 'list', 'map', 'dict', 'dictionary', 'set', 'tuple', 'vector', 'slice', 'option', 'result',
            // Memory/resource management
            'await', 'defer', 'yield', 'using', 'resource', 'lock', 'unlock', 'dispose', 'finalize', 'gc', 'clone', 'copy', 'move', 'drop',
            // Visibility
            'internal', 'external', 'open', 'sealed', 'override', 'extends', 'implements',
            // PHP/JS/Python/Java specific
            '__construct', '__destruct', '__init__', '__call', '__get', '__set', 'global', 'nonlocal', 'del', 'print',
            // SQL-like
            'select', 'insert', 'update', 'delete', 'create', 'alter', 'drop', 'truncate', 'grant', 'revoke', 'commit', 'rollback', 'begin', 'end',
            // Ruby-specific
            'begin', 'ensure', 'rescue', 'elsif', 'unless', 'yield', 'redo', 'retry', 'module', 'mixin',
            // Rust-specific
            'let', 'mut', 'crate', 'pub', 'unsafe', 'trait', 'impl', 'macro_rules',
            // Go-specific
            'go', 'select', 'chan', 'map', 'range', 'defer', 'fallthrough',
            // Swift/Kotlin/Scala/Dart
            'protocol', 'extension', 'open', 'override', 'late', 'init', 'super',
            // TypeScript
            'interface', 'declare', 'readonly', 'keyof', 'infer', 'asserts', 'unknown', 'never',
        ];
        
        $keywordMatches = 0;
        foreach ($keywords as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/iu', $text)) {
                $keywordMatches++;
            }
        }
        if ($keywordMatches > 0) {
            // Cap keyword score to avoid prose with a few programming words triggering it
            $score += min($keywordMatches * 2, 10);
            $categories['language_keywords'] = true;
        }
        
        // Framework-specific hints
        $frameworkHints = [
            // Laravel (PHP)
            'Route::', 'Schema::', 'DB::', 'Auth::', 'Event::', 'Request::', 'response()', 'abort(', 'app(', 'config(', 'view(', 'asset(', 'bcrypt(', 'redirect(', 'validator(', 'factory(',
            'Artisan::', 'Mail::', 'Cache::', 'Log::', 'Gate::', 'Hash::', 'File::', 'URL::', 'Session::', 'Blade::', 'Broadcast::', 'Notification::', 'Queue::', 'Storage::',
            '$this->', '$query->', '->paginate(', '->first(', '::where(', '::find(', 'public function', 'protected function', 'private function', '__construct(', 
            // Symfony (PHP)
            'AbstractController', 'ContainerAware', 'Kernel::', 'RequestStack', 'Response::', 'Yaml::',
            // Rails (Ruby)
            'ActiveRecord::', 'ApplicationController', 'render :', 'redirect_to', 'before_action', 'has_many', 'belongs_to', 'def ', ':symbol',
            // Django (Python)
            'from django', 'import models', 'class Meta', 'def get_context_data(', 'render(request,', 'HttpResponse(', 'models.Model', 'Serializer(', 'path(', 'urlpatterns =',
            // React
            'useState(', 'useEffect(', 'useRef(', 'useCallback(', 'useMemo(', 'useReducer(', 'useContext(', 'useLayoutEffect(', 'React.Component', 'ReactDOM.render', 'props.', 'setState(',
            'className=', '<React.Fragment>', 'export default function', 'export default class', 'export function', 'export const', 'import React', 'import {', '<div', '<span', '</div>', '</span>', '/>',
            // Next.js, Gatsby, Remix
            'getStaticProps', 'getServerSideProps', 'getInitialProps', 'export async function', 'Head from', 'Script from', "Image from 'next/image'", 'Link from', 'loader: async',
            // Vue
            'Vue.component', 'new Vue(', 'export default {', 'props:', 'data() {', 'computed:', 'mounted() {', 'methods:', '@click', 'v-for=', 'v-if=', 'v-model=', ':key=', '<template>', '<script>', '<style>',
            // Angular
            '@Component({', '@NgModule(', 'ngOnInit(', 'ngIf', 'ngFor', 'import {', '@Injectable(', 'this.router.navigate(', 'Observable<', 'HttpClient', 'FormGroup', 'FormControl',
            // Svelte
            '<script>', '<style>', 'export let', '{$', '$:', '<svelte:head>', '<svelte:options>',
            // Spring (Java)
            '@RestController', '@Autowired', '@RequestMapping', '@GetMapping', '@PostMapping', '@Service', '@Repository', 'ResponseEntity<', 'List<', 'Map<',
            // ASP.NET (C#)
            '[HttpGet]', '[HttpPost]', '[ApiController]', '[Route(', 'IActionResult', 'Task<IActionResult>',
            // Flutter (Dart)
            'Widget build', 'BuildContext context', 'State<', 'setState(()', 'StatelessWidget', 'StatefulWidget', 'MaterialApp(', 'Scaffold(', 'Container(', 'child:', 'children:',
            // Android (Kotlin/Java)
            'Activity', 'Fragment', 'Bundle?', 'override fun', 'onCreate(', 'onViewCreated(', 'findViewById(', 'startActivity(',
            // Node.js/Express
            'require(', 'module.exports', 'exports.', 'app.get(', 'app.post(', 'express.Router(', 'next()', 'res.send(', 'res.json(', 'res.status(', 'process.env.', 'async (req, res',
            // FastAPI/Flask (Python)
            'from fastapi', 'from flask', '@app.route(', '@app.get(', '@app.post(', 'JSONResponse(', 'request.form', 'request.args', 'Blueprint(',
            // GraphQL
            'type Query', 'type Mutation', 'resolver:', 'gql`', 'GraphQLObjectType', 'makeExecutableSchema(',
            // Common JS/TS project structure
            'import "', "import '", 'require("', 'require(\'', 'export default', 'export function', 'export class', 'interface ', 'type ', 'implements ', 'extends ',
            // HTML/Blade/PHP/Web templates
            '<?php', '<?= ', '@extends(', '@section(', '@yield(', '@include(', '</html>', '<body>', '<form', '<input', '<select', '<option', '<label', '<table', '<td', '<tr', '<th', '<button',
            // Miscellaneous
            '</', '/>', '<%', '%>', '{%', '%}', '<!--', '-->'
        ];
        $frameworkMatches = 0;
        foreach ($frameworkHints as $hint) {
            if (str_contains($text, $hint)) {
                $frameworkMatches++;
            }
        }
        if ($frameworkMatches > 0) {
            // Cap framework hints more tightly
            $score += min($frameworkMatches * 3, 6);
            $categories['framework_hints'] = true;
        }

        // 6. Code operators / syntax density
        $operators = [
            '->', '=>', '::', '===', '!==', '&&', '||', '!=', '==', 
            '=', '+', '-', '*', '/', '%', '**', // assignment and arithmetic
            '++', '--', // increment/decrement
            '&', '|', '^', '~', '<<', '>>', // bitwise
            '+=', '-=', '*=', '/=', '%=', '**=', '&=', '|=', '^=', '<<=', '>>=', // compound assignment
            '<', '<=', '>', '>=', // comparisons
            '?.', '??', '?:', // null/optional chaining/coalescing (JS, PHP)
            '!', '!!', // logical not/double-negation idioms
            '::=', // strict equal (various langs)
            '...', // spread/rest (JS, PHP, Python)
            '->*', // pointer-to-member (C++)
            ':=', // assignment in some languages (Go, pseudo, Pascal)
            '//', '/*', '*/', '#', // comments indicators (C, JS, Python, Bash)
        ];
        $operatorMatches = 0;
        foreach ($operators as $op) {
            if (str_contains($text, $op)) {
                $operatorMatches++;
            }
        }
        if ($operatorMatches > 0) {
            $score += $operatorMatches * 3;
            $categories['code_operators'] = true;
        }

        $totalChars = mb_strlen($text, 'UTF-8');
        if ($totalChars > 20) {
            $specialChars = preg_match_all('/[{}()\[\];=><!@#$%^&*~]/u', $text);
            if ($specialChars !== false) {
                $ratio = $specialChars / $totalChars;
                // Stricter syntax density: 0.18 instead of 0.15
                if ($ratio > 0.18) {
                    $score += 10;
                    $categories['syntax_density'] = true;
                } elseif ($ratio > 0.12) {
                    $score += 5;
                    $categories['syntax_density'] = true;
                }
            }
        }

        // 7. File references / extensions
        if (preg_match_all('/\b\w+\.(' .
            implode('|', [
                // Code & markup
                'php', 'phtml', 'inc', 'blade\.php',
                'js', 'jsx', 'mjs', 'cjs',
                'ts', 'tsx',
                'go',
                'py', 'pyc', 'pyo', 'pyd',
                'rb', 'rake',
                'pl', 'pm',
                'cs', 'vb', 'fs', 'fsharp',
                'c', 'h', 'cpp', 'cc', 'cxx', 'c++', 'hpp', 'hh', 'hxx',
                'java', 'kt', 'kts', 'scala', 'groovy', 'clj', 'cljs', 'cljc', 'edn',
                'swift', 'm', 'mm',
                'rs',
                'dart',
                'lua',
                'sh', 'bash', 'zsh', 'fish',
                'bat', 'cmd', 'ps1',
                // Markup & config
                'html', 'htm', 'xml', 'xhtml', 'svg',
                'css', 'scss', 'sass', 'less', 'styl', 'pcss',
                'yml', 'yaml', 'toml', 'ini', 'conf', 'config', 'cnf', 'rc',
                'env',
                // Data & serialization
                'json', 'jsonc', 'json5', 'csv', 'tsv',
                'md', 'markdown', 'mdx', 'rst', 'org', 'adoc',
                'txt', 'text', 'log', 'out',
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                // DB & queries
                'sql', 'sqlite', 'db', 'db3', 's3db',
                // Containers, cloud & scripts
                'dockerfile', 'compose', 'tf', 'tfvars', 'tfstate',
                // Archives & binaries
                'zip', 'tar', 'gz', 'tgz', 'rar', '7z', 'bin', 'exe', 'dll', 'so', 'dylib', 'appimage',
                // Other text/code
                'makefile', 'cmake', 'gradle', 'pom', 'bat', 'ini', 'ps1', 'psm1', 'vbs',
            ])
        .')\b/iu', $text, $matches)) {
            $score += count($matches[0]) * 4;
            $categories['file_extensions'] = true;
        }

        return [$score, array_keys($categories)];
    }
}