# Paleta de cores do Server-Hub

A interface usa superfícies claras e neutras, com indigo e roxo como cores de marca. Os estados operacionais usam verde, âmbar e vermelho.

| Papel | Cor | Hex | Tailwind CSS | Uso |
| --- | --- | --- | --- | --- |
| Primária | Indigo 600 | `#4F46E5` | `bg-indigo-600`, `text-indigo-600`, `from-indigo-600` | Início do gradiente da marca e ações principais |
| Secundária | Purple 600 | `#9333EA` | `bg-purple-600`, `text-purple-600`, `to-purple-600` | Destaques, ícones, títulos e ações |
| Secundária escura | Purple 700 | `#7E22CE` | `bg-purple-700`, `text-purple-700` | Hover e ênfase |
| Destaque suave | Purple 100 | `#F3E8FF` | `bg-purple-100` | Navegação ativa e hover suave |
| Fundo | Gray 100 | `#F3F4F6` | `bg-gray-100` | Fundo geral das páginas |
| Superfície | White | `#FFFFFF` | `bg-white` | Cards, barras e painéis |
| Borda | Gray 200 | `#E5E7EB` | `border-gray-200`, `bg-gray-200` | Bordas e campos neutros |
| Texto principal | Gray 900 | `#111827` | `text-gray-900` | Títulos e conteúdo de maior contraste |
| Texto secundário | Gray 700 | `#374151` | `text-gray-700` | Labels e conteúdo auxiliar |
| Texto discreto | Gray 500 | `#6B7280` | `text-gray-500` | Metadados e descrições |
| Sucesso | Green 600 | `#16A34A` | `text-green-600`, `bg-green-600` | Servidor em execução e slot alocado |
| Fundo de sucesso | Green 200 | `#BBF7D0` | `bg-green-200` | Badge de sucesso |
| Atenção | Yellow 600 | `#CA8A04` | `text-yellow-600` | Estados transitórios |
| Fundo de atenção | Amber 200 | `#FDE68A` | `bg-amber-200` | Badge de atenção |
| Erro | Red 600 | `#DC2626` | `text-red-600`, `bg-red-600` | Falhas e ações destrutivas |
| Fundo de erro | Red 200 | `#FECACA` | `bg-red-200` | Badge de erro |

## Gradiente da marca

```html
class="bg-linear-to-r from-indigo-600 to-purple-600"
```

O gradiente deve ficar restrito a ações e detalhes de marca. Cards e áreas de conteúdo permanecem brancos sobre `bg-gray-100`.
