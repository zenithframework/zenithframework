<?php

declare(strict_types=1);

namespace Zenith\Database;

class Paginator
{
    public function __construct(
        protected array $items,
        protected int $total,
        protected int $perPage,
        protected int $currentPage
    ) {
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    public function nextPage(): ?int
    {
        return $this->hasMorePages() ? $this->currentPage + 1 : null;
    }

    public function previousPage(): ?int
    {
        return $this->currentPage > 1 ? $this->currentPage - 1 : null;
    }

    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'data' => $this->items,
            'from' => $this->firstItem(),
            'last_page' => $this->lastPage(),
            'per_page' => $this->perPage,
            'to' => $this->lastItem(),
            'total' => $this->total,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    protected function firstItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }

        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    protected function lastItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }

        return min($this->currentPage * $this->perPage, $this->total);
    }

    public function links(): string
    {
        if ($this->lastPage() <= 1) {
            return '';
        }

        $html = '<nav class="pagination">';

        if ($this->previousPage()) {
            $html .= '<a href="?page=' . $this->previousPage() . '">&laquo; Previous</a>';
        }

        for ($i = 1; $i <= $this->lastPage(); $i++) {
            $active = $i === $this->currentPage ? ' class="active"' : '';
            $html .= '<a href="?page=' . $i . '"' . $active . '>' . $i . '</a>';
        }

        if ($this->hasMorePages()) {
            $html .= '<a href="?page=' . $this->nextPage() . '">Next &raquo;</a>';
        }

        $html .= '</nav>';

        return $html;
    }
}
