@if ($paginator->hasPages())

<div class="d-flex justify-content-between align-items-center mt-4">

    <div class="text-body-secondary small">

        Mostrando

        <strong>{{ $paginator->firstItem() }}</strong>

        a

        <strong>{{ $paginator->lastItem() }}</strong>

        de

        <strong>{{ $paginator->total() }}</strong>

        registros

    </div>

    <nav>

        <ul class="pagination pagination-sm mb-0">

            {{-- Anterior --}}
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">

                <a
                    class="page-link"
                    href="{{ $paginator->previousPageUrl() ?? '#' }}">

                    <i class="cil-chevron-left"></i>

                </a>

            </li>

            @foreach ($elements as $element)

                @if(is_string($element))

                    <li class="page-item disabled">

                        <span class="page-link">

                            {{ $element }}

                        </span>

                    </li>

                @endif

                @if(is_array($element))

                    @foreach($element as $page=>$url)

                        <li class="page-item {{ $page==$paginator->currentPage() ? 'active' : '' }}">

                            <a
                                class="page-link"
                                href="{{ $url }}">

                                {{ $page }}

                            </a>

                        </li>

                    @endforeach

                @endif

            @endforeach

            {{-- Siguiente --}}
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">

                <a
                    class="page-link"
                    href="{{ $paginator->nextPageUrl() ?? '#' }}">

                    <i class="cil-chevron-right"></i>

                </a>

            </li>

        </ul>

    </nav>

</div>

@endif