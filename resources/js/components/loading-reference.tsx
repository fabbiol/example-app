export function loadingPedidoLabel(
    caixaNumber: string | null | undefined,
): string | null {
    const number = caixaNumber?.trim();

    return number ? `#${number}` : null;
}

export default function LoadingReference({
    number,
    caixaNumber,
}: {
    number: string;
    caixaNumber?: string | null;
}) {
    const pedido = loadingPedidoLabel(caixaNumber);

    return (
        <span className="flex flex-col gap-0.5">
            <span className="font-mono text-xs">{number}</span>
            {pedido ? (
                <span className="text-xs text-muted-foreground">
                    Pedido {pedido}
                </span>
            ) : null}
        </span>
    );
}
