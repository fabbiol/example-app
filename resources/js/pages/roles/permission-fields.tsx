import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import type { Option } from '@/types';

export default function RolePermissionFields({
    groups,
    selected = [],
    error,
}: {
    groups: Record<string, Option[]>;
    selected?: string[];
    error?: string;
}) {
    return (
        <div className="grid gap-4">
            <div>
                <Label>Menus e telas</Label>
                <p className="text-sm text-muted-foreground">
                    Marque o que este papel pode ver e usar. O menu do sistema
                    segue exatamente essas opções.
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                {Object.entries(groups).map(([group, items]) => (
                    <fieldset
                        key={group}
                        className="rounded-xl border p-4"
                    >
                        <legend className="px-1 text-sm font-medium">
                            {group}
                        </legend>
                        <div className="grid gap-2">
                            {items.map((item) => (
                                <label
                                    key={item.value}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value={item.value}
                                        defaultChecked={selected.includes(
                                            item.value,
                                        )}
                                        className="size-4 rounded border-input"
                                    />
                                    {item.label}
                                </label>
                            ))}
                        </div>
                    </fieldset>
                ))}
            </div>

            <InputError message={error} />
        </div>
    );
}
