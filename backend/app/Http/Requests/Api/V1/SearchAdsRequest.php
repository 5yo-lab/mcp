<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Http\Responses\ApiErrorResponse;
use App\Services\AdSearchService;
use App\Support\ApiErrorCode;
use App\Support\NicheCatalog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchAdsRequest extends FormRequest
{
    /** @var list<string> */
    private const ALLOWED = ['q', 'niche', 'format', 'status', 'started_after', 'sort', 'cursor', 'per_page'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $unknown = array_diff(array_keys($this->query()), self::ALLOWED);
        if ($unknown !== []) {
            $suggestions = [];
            foreach ($unknown as $name) {
                $guess = AdSearchService::suggestParameter($name);
                $suggestions[$name] = $guess ? "Did you mean \"{$guess}\"?" : 'Remove this parameter.';
            }

            throw new HttpResponseException(
                ApiErrorResponse::make(
                    ApiErrorCode::UnknownParameter,
                    'Unknown query parameter(s).',
                    'Use only documented parameters for v1 search.',
                    false,
                    422,
                    ['unknown' => $unknown, 'suggestions' => $suggestions],
                ),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'niche' => ['sometimes'],
            'format' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string'],
            'started_after' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'sort' => ['sometimes', 'string', 'in:newest,impressions'],
            'cursor' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $details = $validator->errors()->toArray();
        $hints = $this->valueHints($details);

        throw new HttpResponseException(
            ApiErrorResponse::make(
                ApiErrorCode::ValidationError,
                'Invalid search parameters.',
                'Fix the parameters listed in details and retry.',
                false,
                422,
                ['fields' => $details, 'hints' => $hints],
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedFilters(): array
    {
        $data = $this->validated();

        $nicheRaw = $data['niche'] ?? null;
        [$niches, $invalidNiches] = $this->parseNiches($nicheRaw);

        if ($invalidNiches !== []) {
            throw new HttpResponseException(
                ApiErrorResponse::make(
                    ApiErrorCode::ValidationError,
                    'Invalid niche value(s).',
                    'Use slugs from GET /api/v1/niches.',
                    false,
                    422,
                    ['invalid' => $invalidNiches, 'hints' => ['Skincare' => 'skincare', 'nich' => 'niche parameter name']],
                ),
            );
        }

        if ($this->filled('niche') && $niches === []) {
            throw new HttpResponseException(
                ApiErrorResponse::make(
                    ApiErrorCode::ValidationError,
                    'Invalid niche value.',
                    'Call GET /api/v1/niches or use list_niches via MCP for allowed slugs.',
                    false,
                    422,
                    [
                        'hints' => [
                            'niche' => $this->nicheHint((string) $this->query('niche')),
                        ],
                    ],
                ),
            );
        }

        if (isset($data['format']) && AdFormat::tryFrom($data['format']) === null) {
            throw new HttpResponseException(
                ApiErrorResponse::make(
                    ApiErrorCode::ValidationError,
                    'Invalid format.',
                    'Use one of: image, video, carousel.',
                    false,
                    422,
                    ['allowed' => array_column(AdFormat::cases(), 'value')],
                ),
            );
        }

        if (isset($data['status']) && AdStatus::tryFrom($data['status']) === null) {
            throw new HttpResponseException(
                ApiErrorResponse::make(
                    ApiErrorCode::ValidationError,
                    'Invalid status.',
                    'Use active or inactive.',
                    false,
                    422,
                    ['allowed' => array_column(AdStatus::cases(), 'value')],
                ),
            );
        }

        return [
            'q' => $data['q'] ?? null,
            'niche' => $niches,
            'format' => $data['format'] ?? null,
            'status' => $data['status'] ?? null,
            'started_after' => $data['started_after'] ?? null,
            'sort' => $data['sort'] ?? AdSearchService::SORT_NEWEST,
            'cursor' => $data['cursor'] ?? null,
            'per_page' => $data['per_page'] ?? 20,
        ];
    }

    /**
     * @param  array<string, list<string>>  $details
     * @return array<string, string>
     */
    private function valueHints(array $details): array
    {
        $hints = [];
        if (isset($details['niche'])) {
            $hints['niche'] = $this->nicheHint((string) $this->query('niche'));
        }

        return $hints;
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function parseNiches(mixed $nicheRaw): array
    {
        if ($nicheRaw === null) {
            return [[], []];
        }

        $items = is_array($nicheRaw) ? $nicheRaw : explode(',', (string) $nicheRaw);
        $slugs = [];
        $invalid = [];
        foreach ($items as $item) {
            $trim = trim((string) $item);
            if ($trim === '') {
                continue;
            }
            $slug = NicheCatalog::resolveSlug($trim);
            if ($slug === null) {
                $invalid[] = $trim;
            } else {
                $slugs[] = $slug;
            }
        }

        return [array_values(array_unique($slugs)), $invalid];
    }

    private function nicheHint(string $raw): string
    {
        $guess = NicheCatalog::resolveSlug($raw);
        if ($guess !== null) {
            return "Did you mean slug \"{$guess}\"?";
        }
        if (strtolower($raw) === 'skincare' || strcasecmp($raw, 'Skincare') === 0) {
            return 'Did you mean slug "skincare"?';
        }

        return 'Use a slug from /api/v1/niches (e.g. pet-supplies, skincare).';
    }
}
