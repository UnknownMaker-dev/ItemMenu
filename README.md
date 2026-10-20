# ItemMenu

Plugin para PocketMine-MP 5 que coloca itens de menu no inventário dos jogadores (bússola,
relógio, estrela...) e executa comandos quando o item é usado. Ideal para lobbies e servidores
que abrem menus a partir de um item na hotbar.

> 🆓 **Projeto open source e gratuito**, distribuído sob a [licença MIT](LICENSE). Você pode usar,
> estudar, modificar e redistribuir o código livremente, inclusive em servidores comerciais,
> desde que mantenha o aviso de licença.

## Recursos

- Itens configuráveis em `items.yml`: tipo, nome, lore, slot, mundos, tempo de espera,
  permissão e lista de comandos.
- Comandos executados **como o jogador** (com as permissões dele e passando pelos bloqueios de
  comando de outros plugins) ou **pelo console**, escolhido por item (`run-as`) e, se quiser,
  por comando (prefixos `player:` e `console:`). `{player}` funciona nos dois casos; nomes com
  espaço são colocados entre aspas automaticamente.
- Funciona clicando no ar e clicando em um bloco. A ação normal do item (comer, lançar,
  colocar bloco, equipar...) é sempre cancelada, e um clique nunca dispara os comandos duas vezes.
- **Itens fixos**: entregues num slot definido ao entrar e ao renascer, não podem ser movidos,
  trocados, craftados, colocados em baús nem dropados e não caem na morte.
- Os itens do plugin são reconhecidos por uma **marca própria na NBT**. Itens comuns do mesmo
  tipo (uma bússola qualquer) nunca são travados nem executam comandos.
- Se o slot de um item fixo estiver ocupado, o item do jogador é **movido** para um espaço livre
  (ou dropado aos pés dele se o inventário estiver cheio). Nada é apagado.
- Tempo de espera por item e por jogador, com permissão para ignorar.
- Itens habilitados só em alguns mundos (ou em todos).
- `items.yml` validado: entradas inválidas são ignoradas com um aviso claro no console e as
  demais continuam funcionando. YAML quebrado nunca derruba o servidor.
- `/itemmenu reload`, `give` e `list`, todos utilizáveis pelo console.

## Requisitos

- [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) 5 (API `5.0.0`) ou forks compatíveis, como o Altay.
- [Forja](https://github.com/UnknownMaker-dev/Forja) (biblioteca, obrigatória).

## Instalação

1. Instale a [Forja](https://github.com/UnknownMaker-dev/Forja) na pasta `plugins/` do servidor.
2. Coloque o `.phar` do ItemMenu em `plugins/`. Para usar a pasta do código diretamente é
   preciso ter o plugin [DevTools](https://github.com/pmmp/DevTools) instalado.
3. Inicie o servidor. São criados `plugin_data/ItemMenu/config.yml` e
   `plugin_data/ItemMenu/items.yml` (com exemplos).
4. Edite os arquivos e use `/itemmenu reload` (não precisa reiniciar).

## Comandos

| Comando | Permissão | Descrição |
|---|---|---|
| `/itemmenu` ou `/itemmenu ajuda` | qualquer uma abaixo | Mostra os subcomandos que você pode usar. Alias: `/imenu`. |
| `/itemmenu reload` | `itemmenu.command.reload` | Recarrega `config.yml` e `items.yml` e reaplica os itens fixos de quem está online. |
| `/itemmenu give <jogador> [id]` | `itemmenu.command.give` | Sem `id`: entrega (ou reorganiza) todos os itens fixos do jogador. Com `id`: entrega aquele item. |
| `/itemmenu list` | `itemmenu.command.list` | Lista os itens carregados com tipo, slot, mundos, espera e número de comandos. |

Todos os subcomandos funcionam no console.

## Permissões

| Permissão | Padrão | Descrição |
|---|---|---|
| `itemmenu.command.reload` | OP | Usar `/itemmenu reload`. |
| `itemmenu.command.give` | OP | Usar `/itemmenu give`. |
| `itemmenu.command.list` | OP | Usar `/itemmenu list`. |
| `itemmenu.bypass.cooldown` | OP | Usar itens de menu sem tempo de espera (o intervalo anti-duplo-clique continua valendo). |
| a definida em `permission:` de cada item | ninguém | Necessária para usar aquele item. Conceda com o seu plugin de permissões. |

## Configuração

### `config.yml`

```yaml
prefix: "&8[&bItemMenu&8]&r "   # prefixo das mensagens (& ou §)
anti-double-click: 0.4          # segundos mínimos entre dois usos de itens de menu (0 a 5)
give-delay-ticks: 5             # atraso para entregar os itens fixos ao entrar/renascer (0 a 200)
remove-unknown-items: true      # apaga itens do plugin cujo id saiu do items.yml

messages:                       # "" desativa a mensagem
  world-disabled: "&cEste item não pode ser usado no mundo &f{world}&c."
  cooldown: "&cAguarde &f{time} &cpara usar este item de novo."
  no-permission: "&cVocê não tem permissão para usar este item."
  locked: "&cEste item é fixo: não pode ser movido nem dropado."
  slot-moved: "&eO item que estava no slot &f{slot} &efoi movido para outro espaço do inventário."
  slot-dropped: "&eSeu inventário estava cheio: o item que estava no slot &f{slot} &efoi dropado no chão."
```

| Chave | Padrão | Descrição |
|---|---|---|
| `prefix` | `&8[&bItemMenu&8]&r ` | Prefixo de todas as mensagens do plugin. |
| `anti-double-click` | `0.4` | Um toque pode chegar ao servidor como clique no bloco e no ar ao mesmo tempo; este intervalo impede que os comandos rodem duas vezes. |
| `give-delay-ticks` | `5` | Pequeno atraso para não brigar com plugins que mexem no inventário ao entrar. |
| `remove-unknown-items` | `true` | Itens criados pelo plugin cujo id não existe mais são removidos ao entrar, renascer e no reload. Itens comuns nunca são tocados. |
| `messages.*` | — | Variáveis: `{world}`, `{time}`, `{slot}`. |

### `items.yml`

Cada entrada dentro de `items` é um item; o nome da entrada é o id (letras minúsculas, números,
`_` ou `-`, até 32 caracteres).

| Chave | Tipo | Padrão | Descrição |
|---|---|---|---|
| `item` | texto | — (obrigatória) | Tipo do item: `compass`, `minecraft:clock`, `nether_star`, ou um item customizado do servidor (`meupack:menu`). |
| `name` | texto | nome normal | Nome exibido, com cores `&`. |
| `lore` | lista | vazia | Linhas de descrição. |
| `fixed` | true/false | `false` | Item fixo: entregue no `slot` ao entrar/renascer, travado e mantido na morte. Sem isso, o item só é entregue por `/itemmenu give` e pode ser movido e dropado normalmente. |
| `slot` | 1 a 36 | — | Slot do inventário (1 a 9 = hotbar). Obrigatório em itens fixos; em itens não fixos é o slot preferido, usado se estiver vazio. |
| `worlds` | lista | vazia | Pastas de mundo onde o item funciona. Vazia = todos. |
| `cooldown` | número | `0` | Segundos de espera entre usos, por jogador. |
| `permission` | texto | — | Permissão exigida para usar o item. |
| `run-as` | `player`/`console` | `player` | Quem executa os comandos sem prefixo. |
| `commands` | lista | vazia | Comandos sem `/`. Prefixe com `player:` ou `console:` para escolher quem executa aquele comando. `{player}` = nome de quem usou. |

O que torna uma entrada inválida (ela é ignorada com aviso no console): falta de `item`, item
desconhecido, `slot` fora de 1 a 36, item fixo sem `slot`, dois itens fixos no mesmo slot, id
inválido ou repetido, entrada que não é uma seção. Valores errados em chaves opcionais
(`cooldown: abc`, `run-as: admin`...) só geram aviso e usam o padrão.

Exemplo (o mesmo que vem em `resources/items.yml`):

```yaml
items:
  menu:
    item: compass
    name: "&r&bMenu do servidor &7(clique)"
    lore:
      - "&7Clique com o item na mão"
      - "&7para abrir o menu."
    fixed: true
    slot: 1
    worlds: []
    cooldown: 1
    run-as: player
    commands:
      - "menu"            # troque pelo comando do seu plugin de menus

  info:
    item: clock
    name: "&r&eInformações"
    lore:
      - "&7Mostra quem está online."
    fixed: true
    slot: 9
    worlds: []
    cooldown: 3
    run-as: console
    commands:
      - "tell {player} Olá, {player}! Estes são os jogadores online:"
      - "player: list"

  velocidade:
    item: feather
    name: "&r&fPena da velocidade"
    lore:
      - "&7Dá velocidade por 10 segundos."
    fixed: false
    worlds:
      - world
    cooldown: 30
    run-as: console
    commands:
      - "effect {player} speed 10 1"
```

## Como usar

1. Instale a Forja e o ItemMenu e inicie o servidor uma vez.
2. Edite `plugin_data/ItemMenu/items.yml`: crie uma entrada por item, com o tipo, os comandos e,
   para itens de hotbar, `fixed: true` e o `slot`.
3. Rode `/itemmenu reload` (no jogo ou no console). Confira o console: entradas com erro aparecem
   com o motivo, e `/itemmenu list` mostra o que foi carregado.
4. Jogadores online recebem os itens fixos na hora; quem entrar ou renascer recebe
   automaticamente. Se o slot estiver ocupado, o item que estava lá é movido para outro espaço.
5. Para itens não fixos (como a pena do exemplo), use `/itemmenu give <jogador> <id>`; isso
   também funciona pelo console ou por outros plugins (NPCs, recompensas...).
6. Clique com o item na mão (no ar ou num bloco) para executar os comandos.

## Limitações

- Itens fixos travam o slot também para o próprio jogador em modo criativo; para reorganizar,
  mude o `slot` no `items.yml` e use `/itemmenu reload`.
- Se outro plugin limpar o inventário do jogador, os itens fixos voltam ao entrar, ao renascer,
  no `/itemmenu reload` ou com `/itemmenu give <jogador>`.

## Contribuindo

Issues e pull requests são bem-vindos. Ao contribuir, você concorda que sua contribuição será
licenciada sob a mesma licença MIT do projeto.

## Licença

Distribuído sob a licença MIT — veja [LICENSE](LICENSE).

## Aviso de responsabilidade

Este plugin é fornecido gratuitamente, **"no estado em que se encontra" (as is)**, sem garantia
de qualquer tipo, expressa ou implícita. Os autores e mantenedores **não se responsabilizam** por
perda de dados, mundos ou itens, quedas ou falhas do servidor, prejuízos financeiros, nem por
qualquer uso que terceiros façam deste código ou dos servidores em que ele for instalado. Faça
backup antes de instalar ou atualizar e teste em um servidor de testes antes de usar em
produção. Ao usar, modificar ou redistribuir este plugin, você assume total responsabilidade
por isso.

Este projeto não é afiliado, endossado ou patrocinado pela Mojang, Microsoft, PocketMine-MP,
nem pelos detentores dos direitos de obras ou marcas eventualmente citadas.
