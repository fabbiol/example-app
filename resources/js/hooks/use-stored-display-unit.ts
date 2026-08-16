import { useCallback, useSyncExternalStore } from 'react';
import type { QuantityUnit } from '@/lib/quantity';

export type DisplayUnit = QuantityUnit;

const listeners = new Map<string, Set<() => void>>();

function subscribe(key: string, onStoreChange: () => void): () => void {
    let set = listeners.get(key);

    if (!set) {
        set = new Set();
        listeners.set(key, set);
    }

    set.add(onStoreChange);

    return () => {
        set.delete(onStoreChange);
    };
}

function read(key: string): DisplayUnit {
    if (typeof window === 'undefined') {
        return 'm3';
    }

    const saved = window.localStorage.getItem(key);

    return saved === 'm3' || saved === 'ton' ? saved : 'm3';
}

export function useStoredDisplayUnit(
    storageKey: string,
): [DisplayUnit, (unit: DisplayUnit) => void] {
    const subscribeToKey = useCallback(
        (onStoreChange: () => void) => subscribe(storageKey, onStoreChange),
        [storageKey],
    );

    const unit = useSyncExternalStore(
        subscribeToKey,
        () => read(storageKey),
        (): DisplayUnit => 'm3',
    );

    const setUnit = (next: DisplayUnit): void => {
        window.localStorage.setItem(storageKey, next);
        listeners.get(storageKey)?.forEach((listener) => listener());
    };

    return [unit, setUnit];
}
