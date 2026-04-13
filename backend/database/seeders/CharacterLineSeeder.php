<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\CharacterLine;
use Illuminate\Database\Seeder;

class CharacterLineSeeder extends Seeder
{
    public function run(): void
    {
        $marina = Character::where('slug', 'marina')->first();
        $zina = Character::where('slug', 'grandma_zina')->first();
        $barsik = Character::where('slug', 'cat_barsik')->first();

        if ($marina) $this->seedMarina($marina->id);
        if ($zina) $this->seedGrandmaZina($zina->id);
        if ($barsik) $this->seedCatBarsik($barsik->id);
    }

    private function seedMarina(int $id): void
    {
        $lines = [
            // order_appear
            ['trigger' => 'order_appear', 'conditions' => ['first_time' => true], 'text' => 'Привет-привет! Я Марина, у меня кафешка за углом. Сделаешь мне кофеёчек?', 'priority' => 90],
            ['trigger' => 'order_appear', 'conditions' => ['relationship' => 'loyal'], 'text' => 'О, мой любимый помощник! Без тебя кафе бы не выжило!', 'priority' => 80],
            ['trigger' => 'order_appear', 'conditions' => ['relationship' => 'familiar'], 'text' => 'Снова ты! Отлично, у меня как раз срочный заказик!', 'priority' => 70],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'morning'], 'text' => 'Утренний раш! Помоги, пока очередь не разбежалась!', 'priority' => 60],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'evening'], 'text' => 'Вечерний кофеёк — лучший кофеёк! Давай, выручай!', 'priority' => 60],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'У меня новый заказик! Поможешь?', 'priority' => 40],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Заказ пришёл! Скорее-скорее!', 'priority' => 40],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Ещё один! Моё кафе сегодня просто нарасхват!', 'priority' => 40],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Клиенты ждут! Давай сделаем это быстро!', 'priority' => 35],
            ['trigger' => 'order_appear', 'conditions' => ['order_level' => 'high'], 'text' => 'Ого, VIP-заказ! Тут нужен настоящий профессионал!', 'priority' => 75],

            // order_complete
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'fast'], 'text' => 'Ого, как быстро! Ты прямо бариста от бога!', 'priority' => 80],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'fast'], 'text' => 'Молниеносно! Мне бы такую скорость по утрам!', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'slow'], 'text' => 'Ну наконец-то! Я уже думала, сама буду варить!', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'normal'], 'text' => 'Отличненько! Клиент доволен, я довольна!', 'priority' => 60],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=5'], 'text' => 'Пять заказов подряд! Тебе надо к нам на работу!', 'priority' => 85],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=3'], 'text' => 'Три подряд! Ты в ударе сегодня!', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => ['order_level' => 'high'], 'text' => 'Какая красота! Это шедевр, а не кофе!', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Супер! Держи монетки!', 'priority' => 40],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Готово! Ты лучшая!', 'priority' => 40],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Замечательно! Клиент в восторге!', 'priority' => 35],
            ['trigger' => 'order_complete', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Как всегда идеально! Ты мой лучший помощник!', 'priority' => 80],

            // order_partial
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => 'О, уже что-то есть! Давай остальное!', 'priority' => 50],
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => 'Часть заказа готова! Ещё чуть-чуть!', 'priority' => 50],
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => 'Ура, начало положено! Осталось немного!', 'priority' => 45],

            // order_waiting_long
            ['trigger' => 'order_waiting_long', 'conditions' => ['mood' => 'impatient'], 'text' => 'Эй, ну где мой заказик? Клиенты ждут, а я нервничаю!', 'priority' => 70],
            ['trigger' => 'order_waiting_long', 'conditions' => [], 'text' => 'Хм, ну что там? Кофе сам себя не сварит!', 'priority' => 60],
            ['trigger' => 'order_waiting_long', 'conditions' => [], 'text' => 'Ау! Заказ всё ещё ждёт!', 'priority' => 55],

            // order_waiting_very_long
            ['trigger' => 'order_waiting_very_long', 'conditions' => [], 'text' => 'Ладно, я уже не нервничаю... я в панике! Где заказ?!', 'priority' => 80],
            ['trigger' => 'order_waiting_very_long', 'conditions' => [], 'text' => 'Клиент уже ушёл и вернулся. Дважды. Помоги!', 'priority' => 75],

            // chain_merge
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => 'Ух ты, какое комбо! Это как идеальный латте-арт!', 'priority' => 70],
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => 'Вау, цепочка! Ты как кофемашина — без остановки!', 'priority' => 65],
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => 'Какая серия! У меня аж мурашки!', 'priority' => 60],

            // high_level_item
            ['trigger' => 'high_level_item', 'conditions' => [], 'text' => 'Ого! Это ж шедевр! Мне бы такое в меню!', 'priority' => 70],
            ['trigger' => 'high_level_item', 'conditions' => [], 'text' => 'Красотища! Это можно на витрину ставить!', 'priority' => 65],

            // energy_depleted
            ['trigger' => 'energy_depleted', 'conditions' => [], 'text' => 'Выдохлась? Понимаю, я тоже без кофе не могу. Отдохни минутку!', 'priority' => 70],
            ['trigger' => 'energy_depleted', 'conditions' => [], 'text' => 'Заряды кончились? Бывает! Перерыв на кофе — это святое!', 'priority' => 65],

            // player_return
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'morning'], 'text' => 'Доброе утро! Кофе уже варится, заходи скорей!', 'priority' => 70],
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'afternoon'], 'text' => 'Обеденный перерыв? Самое время для кофеёчка!', 'priority' => 65],
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'evening'], 'text' => 'Вечерний визит! Давай вместе закроем план!', 'priority' => 65],
            ['trigger' => 'player_return', 'conditions' => [], 'text' => 'Ты вернулась! Тут без тебя хаос был!', 'priority' => 50],
            ['trigger' => 'player_return', 'conditions' => [], 'text' => 'О, привет! Заказы накопились, давай разгребать!', 'priority' => 50],

            // idle_on_field
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => 'Эй, задумалась? У меня тут очередь собирается!', 'priority' => 60],
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => 'Алло-алло! Кто-нибудь дома?', 'priority' => 55],
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => 'Перерыв окончен! За работу!', 'priority' => 50],

            // merge_nearby
            ['trigger' => 'merge_nearby', 'conditions' => [], 'text' => 'О, вижу процесс идёт! Так держать!', 'priority' => 50],
            ['trigger' => 'merge_nearby', 'conditions' => [], 'text' => 'Ловко! Продолжай в том же духе!', 'priority' => 45],

            // wrong_merge_attempt
            ['trigger' => 'wrong_merge_attempt', 'conditions' => [], 'text' => 'Ой, это не сочетается! Как молоко и лимон!', 'priority' => 60],
            ['trigger' => 'wrong_merge_attempt', 'conditions' => [], 'text' => 'Не-не-не, так не получится!', 'priority' => 55],

            // event_start
            ['trigger' => 'event_start', 'conditions' => [], 'text' => 'Новый ивент! Это же праздник для нашего кафе!', 'priority' => 70],

            // Fallback generic lines
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Новый заказик! Справишься?', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Спасибо! Ты супер!', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Ну что, готова? Вперёд!', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Класс! Ещё один довольный клиент!', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Заказ номер... а, неважно! Просто сделай!', 'priority' => 15, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Идеально! Как всегда!', 'priority' => 15, 'max_shows' => 50],

            // More variety
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'night'], 'text' => 'Полуночный кофе? Уважаю! Но потом спать, ладно?', 'priority' => 65],
            ['trigger' => 'chain_merge', 'conditions' => ['streak' => '>=3'], 'text' => 'Тройное комбо! Ты горишь сегодня!', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=10'], 'text' => 'ДЕСЯТЬ подряд?! Ты легенда! Я назову напиток в твою честь!', 'priority' => 95],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'night'], 'text' => 'Поздний заказ! Кто-то не может уснуть без кофе!', 'priority' => 60],
            ['trigger' => 'high_level_item', 'conditions' => ['order_level' => 'high'], 'text' => 'Золотой кофейный сет! Я даже боюсь его трогать!', 'priority' => 85],

            // Additional filler
            ['trigger' => 'merge_nearby', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Мой лучший бариста работает! Обожаю!', 'priority' => 60],
            ['trigger' => 'idle_on_field', 'conditions' => ['relationship' => 'familiar'], 'text' => 'Задумалась о рецепте? Понимаю, это важно!', 'priority' => 55],
            ['trigger' => 'energy_depleted', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Отдыхай, дорогая! Ты столько для меня сделала!', 'priority' => 75],
            ['trigger' => 'order_waiting_long', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Я знаю, ты стараешься! Просто клиент нервничает...', 'priority' => 65],
            ['trigger' => 'order_appear', 'conditions' => ['streak' => '>=5'], 'text' => 'Ты сегодня на огне! Ещё один заказик?', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => ['time_of_day' => 'morning'], 'text' => 'Утренний заказ готов! Лучшее начало дня!', 'priority' => 60],
            ['trigger' => 'wrong_merge_attempt', 'conditions' => ['relationship' => 'familiar'], 'text' => 'Ты же знаешь, что так не работает! Пробуй другое!', 'priority' => 60],
            ['trigger' => 'order_partial', 'conditions' => ['speed' => 'fast'], 'text' => 'Быстро двигаешься! Осталось совсем немного!', 'priority' => 55],
            ['trigger' => 'event_start', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Новый ивент! Мы с тобой точно победим!', 'priority' => 75],
            ['trigger' => 'player_return', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Моя звёздочка вернулась! Кафе сразу ожило!', 'priority' => 75],

            // Extra generics
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Тук-тук! Новый заказ стучится!', 'priority' => 30, 'max_shows' => 30],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Ещё один в копилку! Молодец!', 'priority' => 30, 'max_shows' => 30],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'О! Интересный заказ пришёл!', 'priority' => 25, 'max_shows' => 30],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Готово-готово-готово! Обожаю!', 'priority' => 25, 'max_shows' => 30],
        ];

        $this->insertLines($id, $lines);
    }

    private function seedGrandmaZina(int $id): void
    {
        $lines = [
            // order_appear
            ['trigger' => 'order_appear', 'conditions' => ['first_time' => true], 'text' => 'Здравствуй, деточка! Я бабушка Зина. Поможешь старушке с пирожками?', 'priority' => 90],
            ['trigger' => 'order_appear', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Солнышко моё пришло! Давай-ка испечём что-нибудь вкусненькое!', 'priority' => 80],
            ['trigger' => 'order_appear', 'conditions' => ['relationship' => 'familiar'], 'text' => 'А вот и помощница моя! Тесто уже подходит, давай скорей!', 'priority' => 70],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'morning'], 'text' => 'С утречка самое время печь! Пока тесто свежее!', 'priority' => 60],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'evening'], 'text' => 'Вечерком-то самое время печь. Давай-ка замесим тесто, солнышко!', 'priority' => 60],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Деточка, у меня тут заказик для тебя!', 'priority' => 40],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Солнышко, поможешь? Руки-то у меня уже не те...', 'priority' => 40],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'А вот и новый рецептик! Попробуем?', 'priority' => 35],
            ['trigger' => 'order_appear', 'conditions' => ['order_level' => 'high'], 'text' => 'Ох, серьёзный заказ! Но я в тебя верю, деточка!', 'priority' => 75],

            // order_complete
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'fast'], 'text' => 'Вот это расторопная! Моя внучка бы так не успела!', 'priority' => 80],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'fast'], 'text' => 'Ай, молодец! Быстрые руки — золотые руки!', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'slow'], 'text' => 'Ничего-ничего, терпение — лучшая приправа, деточка.', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'normal'], 'text' => 'Хорошо получилось! Как у меня в молодости!', 'priority' => 60],
            ['trigger' => 'order_complete', 'conditions' => ['order_level' => 'high'], 'text' => 'Ох, какая красота получилась! Хоть на выставку неси!', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=5'], 'text' => 'Пять штук подряд! Да ты мастерица, деточка!', 'priority' => 85],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=3'], 'text' => 'Три заказа! Бабушка гордится!', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Спасибо, солнышко! Держи пирожок... то есть монетки!', 'priority' => 40],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Красота! Как бабушка учила!', 'priority' => 40],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Молодец, деточка! На вот тебе!', 'priority' => 35],
            ['trigger' => 'order_complete', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Моя любимая помощница! Что бы я без тебя делала!', 'priority' => 80],

            // order_partial
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => 'Потихонечку, помаленечку... Так и пирог собирается!', 'priority' => 50],
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => 'Хорошее начало! Продолжай, солнышко!', 'priority' => 50],

            // order_waiting_long
            ['trigger' => 'order_waiting_long', 'conditions' => [], 'text' => 'Я не тороплю, но тесто-то подходит, ждать не будет...', 'priority' => 65],
            ['trigger' => 'order_waiting_long', 'conditions' => [], 'text' => 'Деточка, ты не забыла про мой заказик?', 'priority' => 60],

            // order_waiting_very_long
            ['trigger' => 'order_waiting_very_long', 'conditions' => [], 'text' => 'Ох... тесто перестояло. Ну ничего, замесим новое!', 'priority' => 75],

            // chain_merge
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => 'Ох ты ж! Как блинчики на сковородке — один за другим!', 'priority' => 70],
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => 'Серия! Как бабушка в молодости — раз-два-три!', 'priority' => 65],

            // high_level_item
            ['trigger' => 'high_level_item', 'conditions' => [], 'text' => 'Батюшки, какой торт! У меня в молодости такие на свадьбу пекли!', 'priority' => 70],
            ['trigger' => 'high_level_item', 'conditions' => [], 'text' => 'Красота-то какая! Загляденье!', 'priority' => 65],

            // energy_depleted
            ['trigger' => 'energy_depleted', 'conditions' => [], 'text' => 'Устала, милая? Садись, я чайку налью. Отдых — он тоже нужен.', 'priority' => 70],
            ['trigger' => 'energy_depleted', 'conditions' => [], 'text' => 'Отдыхай, деточка. Бабушка подождёт.', 'priority' => 65],

            // player_return
            ['trigger' => 'player_return', 'conditions' => [], 'text' => 'А вот и ты! Я тут пирожки напекла, угощайся!', 'priority' => 55],
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'morning'], 'text' => 'С добрым утречком! Я тут шарлотку затеяла!', 'priority' => 65],
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'evening'], 'text' => 'Вечерок-то какой! Давай чаю попьём и поработаем!', 'priority' => 65],
            ['trigger' => 'player_return', 'conditions' => [], 'text' => 'Вернулась! А я уж волноваться начала!', 'priority' => 50],

            // idle_on_field
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => 'Замечталась, солнышко? Давай-ка руки в тесто!', 'priority' => 55],
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => 'Чего сидим? Пирожки сами себя не испекут!', 'priority' => 50],

            // merge_nearby
            ['trigger' => 'merge_nearby', 'conditions' => [], 'text' => 'Так-так, правильно делаешь, деточка!', 'priority' => 50],
            ['trigger' => 'merge_nearby', 'conditions' => [], 'text' => 'Вот так, потихонечку!', 'priority' => 45],

            // wrong_merge_attempt
            ['trigger' => 'wrong_merge_attempt', 'conditions' => [], 'text' => 'Ой, деточка, это не сочетается. Как селёдка с вареньем!', 'priority' => 60],
            ['trigger' => 'wrong_merge_attempt', 'conditions' => [], 'text' => 'Не-не-не, солнышко! Это как суп с конфетами!', 'priority' => 55],

            // event_start
            ['trigger' => 'event_start', 'conditions' => [], 'text' => 'Ой, праздник! Я уже засучила рукава!', 'priority' => 70],

            // Fallback generics
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Деточка, заказик!', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Спасибо, солнышко!', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Помоги старушке!', 'priority' => 15, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Бабушка довольна!', 'priority' => 15, 'max_shows' => 50],

            // Extras
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'night'], 'text' => 'Полуночница! Ну ладно, бабушка тоже не спит...', 'priority' => 60],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=10'], 'text' => 'Десять заказов! Ты меня в молодость вернула, деточка!', 'priority' => 95],
            ['trigger' => 'chain_merge', 'conditions' => ['streak' => '>=3'], 'text' => 'Тройная серия! Как три слоя у Наполеона!', 'priority' => 75],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'night'], 'text' => 'Ночью печь — особая романтика, деточка!', 'priority' => 55],
            ['trigger' => 'merge_nearby', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Любуюсь, как ты работаешь! Мастерица!', 'priority' => 60],
            ['trigger' => 'idle_on_field', 'conditions' => ['relationship' => 'familiar'], 'text' => 'Эй, солнышко, не засыпай! У нас дела!', 'priority' => 55],
            ['trigger' => 'energy_depleted', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Отдохни, родная. Я тебе пирожков оставлю!', 'priority' => 75],
            ['trigger' => 'order_waiting_long', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Я жду, но не тороплю. Знаю, ты стараешься!', 'priority' => 65],
            ['trigger' => 'high_level_item', 'conditions' => ['order_level' => 'high'], 'text' => 'Королевский торт! Такой только на коронацию пекут!', 'priority' => 80],
            ['trigger' => 'order_appear', 'conditions' => ['streak' => '>=5'], 'text' => 'Вижу, руки горячие! Ещё заказик?', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => ['time_of_day' => 'morning'], 'text' => 'Утренний пирожок — к удаче! Бабушкина примета!', 'priority' => 60],
            ['trigger' => 'wrong_merge_attempt', 'conditions' => ['relationship' => 'familiar'], 'text' => 'Не туда, солнышко! Бабушка подскажет!', 'priority' => 60],
            ['trigger' => 'order_partial', 'conditions' => ['speed' => 'fast'], 'text' => 'Шустренько! Ещё немножко, и готово!', 'priority' => 55],
            ['trigger' => 'event_start', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Новый ивент! Мы с тобой всё испечём, деточка!', 'priority' => 75],
            ['trigger' => 'player_return', 'conditions' => ['relationship' => 'loyal'], 'text' => 'Солнышко вернулось! Бабушка скучала!', 'priority' => 75],

            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Заказик-пирожочек пришёл!', 'priority' => 25, 'max_shows' => 30],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Вкуснота! Как из печки!', 'priority' => 25, 'max_shows' => 30],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Ну-ка, давай посмотрим, что тут у нас!', 'priority' => 30, 'max_shows' => 30],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Хорошая работа! Держи гостинчик!', 'priority' => 30, 'max_shows' => 30],
        ];

        $this->insertLines($id, $lines);
    }

    private function seedCatBarsik(int $id): void
    {
        $lines = [
            // order_appear
            ['trigger' => 'order_appear', 'conditions' => ['first_time' => true], 'text' => '*потягивается* Ладно, я Барсик. Неси мне... вот это. Только без фокусов.', 'priority' => 90],
            ['trigger' => 'order_appear', 'conditions' => ['relationship' => 'loyal'], 'text' => '*мурлычет* Ну... допустим, я рад тебя видеть. Допустим.', 'priority' => 80],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => 'Мррр... ладно, неси. Но это не значит, что я доволен.', 'priority' => 50],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*зевает* Ещё заказ? Ну давай, что ли...', 'priority' => 45],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*смотрит свысока* Можешь начинать. Я разрешаю.', 'priority' => 45],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*демонстративно садится* Ну?', 'priority' => 40],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'night'], 'text' => '*светящиеся глаза в темноте* Ночь — моё время. Давай.', 'priority' => 60],
            ['trigger' => 'order_appear', 'conditions' => ['time_of_day' => 'morning'], 'text' => '*зевает* Ты что, с утра? Дай поспать... ладно, давай.', 'priority' => 60],
            ['trigger' => 'order_appear', 'conditions' => ['order_level' => 'high'], 'text' => '*приподнимает бровь* О, это интересно. Может, ты не безнадёжна.', 'priority' => 75],

            // order_complete
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'fast'], 'text' => '*задирает нос* Ну... допустимо.', 'priority' => 75],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'fast'], 'text' => '*моргает* Хм. Быстро. Подозрительно быстро.', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'slow'], 'text' => '*зевает* Я уже три раза вздремнул, пока ты возилась.', 'priority' => 70],
            ['trigger' => 'order_complete', 'conditions' => ['speed' => 'slow'], 'text' => '*потягивается* А, уже? Я думал, к зиме закончишь.', 'priority' => 65],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=3'], 'text' => '*прищуривается* Хм, ты начинаешь мне нравиться. Не рассказывай никому.', 'priority' => 80],
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=5'], 'text' => '*мурлычет* Ладно-ладно, неплохо работаешь. Для человека.', 'priority' => 85],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => '*принюхивается* Сойдёт.', 'priority' => 40],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => '*отворачивается* Ну ок.', 'priority' => 40],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => '*лениво машет хвостом* Принято.', 'priority' => 35],
            ['trigger' => 'order_complete', 'conditions' => ['relationship' => 'loyal'], 'text' => '*тихо мурлычет* Ты... ничего. Для человека.', 'priority' => 80],
            ['trigger' => 'order_complete', 'conditions' => ['order_level' => 'high'], 'text' => '*глаза расширяются* О! Это... это великолепно. Но я не скажу этого вслух.', 'priority' => 80],

            // order_partial
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => '*наблюдает одним глазом* Продолжай. Я слежу.', 'priority' => 50],
            ['trigger' => 'order_partial', 'conditions' => [], 'text' => '*приоткрывает глаз* Ну хоть что-то...', 'priority' => 45],

            // order_waiting_long
            ['trigger' => 'order_waiting_long', 'conditions' => [], 'text' => '*демонстративно отворачивается*', 'priority' => 65],
            ['trigger' => 'order_waiting_long', 'conditions' => [], 'text' => '*стучит хвостом по полу* Мне. Скучно.', 'priority' => 60],

            // order_waiting_very_long
            ['trigger' => 'order_waiting_very_long', 'conditions' => [], 'text' => '*ложится на заказ* Раз ты не несёшь, я посплю на нём.', 'priority' => 75],

            // chain_merge
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => '*уши торчком* О! Блестяшки!', 'priority' => 70],
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => '*следит за предметами* Интересно... очень интересно...', 'priority' => 65],
            ['trigger' => 'chain_merge', 'conditions' => [], 'text' => '*хищный взгляд* Ещё! ЕЩЁ!', 'priority' => 65],

            // high_level_item
            ['trigger' => 'high_level_item', 'conditions' => [], 'text' => '*расширяет зрачки* Блестящее... моё!.. то есть... твоё. Наверное.', 'priority' => 70],
            ['trigger' => 'high_level_item', 'conditions' => [], 'text' => '*тянет лапу* Красивое... не трогай, я первый увидел!', 'priority' => 65],

            // energy_depleted
            ['trigger' => 'energy_depleted', 'conditions' => [], 'text' => '*ложится на поле* Ну и я отдохну тогда. Не буди.', 'priority' => 70],
            ['trigger' => 'energy_depleted', 'conditions' => [], 'text' => '*сворачивается клубком* Наконец-то тишина...', 'priority' => 65],

            // player_return
            ['trigger' => 'player_return', 'conditions' => [], 'text' => '*делает вид, что не заметил* Ах, это ты. Ну допустим.', 'priority' => 55],
            ['trigger' => 'player_return', 'conditions' => [], 'text' => '*медленно поворачивает голову* Пришла? Мне всё равно. Абсолютно.', 'priority' => 55],
            ['trigger' => 'player_return', 'conditions' => ['relationship' => 'loyal'], 'text' => '*бежит навстречу и останавливается* Я НЕ соскучился. Понятно?!', 'priority' => 80],
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'night'], 'text' => '*глаза светятся* Наконец, кто-то нормальный. Ночные жители, объединяйтесь!', 'priority' => 65],

            // idle_on_field
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => '*тыкает лапой в экран* Эй. ЭЙ. Мне скучно.', 'priority' => 60],
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => '*садится на предмет* Если не играешь — я займу место.', 'priority' => 55],
            ['trigger' => 'idle_on_field', 'conditions' => [], 'text' => '*катает предмет лапой* Ну? Делать нечего?', 'priority' => 50],

            // merge_nearby
            ['trigger' => 'merge_nearby', 'conditions' => [], 'text' => '*наблюдает за предметами* Если это для меня — одобряю.', 'priority' => 55],
            ['trigger' => 'merge_nearby', 'conditions' => [], 'text' => '*следит за движением* Блестящее... движется... интересно...', 'priority' => 50],

            // wrong_merge_attempt
            ['trigger' => 'wrong_merge_attempt', 'conditions' => [], 'text' => '*фыркает* Даже я знаю, что это не работает.', 'priority' => 60],
            ['trigger' => 'wrong_merge_attempt', 'conditions' => [], 'text' => '*качает головой* Нет. Просто нет.', 'priority' => 55],

            // event_start
            ['trigger' => 'event_start', 'conditions' => [], 'text' => '*приподнимается* Что-то новенькое? Ладно, посмотрим.', 'priority' => 65],

            // Fallback generics
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*молча смотрит*', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => 'Мяу.', 'priority' => 20, 'max_shows' => 50],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*хвост дёргается*', 'priority' => 15, 'max_shows' => 50],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => '*моргает*', 'priority' => 15, 'max_shows' => 50],

            // Extras
            ['trigger' => 'order_complete', 'conditions' => ['streak' => '>=10'], 'text' => '*МУРЛЫЧЕТ НА ВСЮ КОМНАТУ* Ты... ты моя любимая... ТО ЕСТЬ... просто неплохо! Всё!', 'priority' => 95],
            ['trigger' => 'chain_merge', 'conditions' => ['streak' => '>=3'], 'text' => '*прыгает* ДА! Ещё! БОЛЬШЕ БЛЕСТЯШЕК!', 'priority' => 75],
            ['trigger' => 'merge_nearby', 'conditions' => ['relationship' => 'loyal'], 'text' => '*мурлычет тихонько* Это между нами, ладно?', 'priority' => 65],
            ['trigger' => 'idle_on_field', 'conditions' => ['relationship' => 'familiar'], 'text' => '*кладёт лапу на руку* ...ну? Давай уже.', 'priority' => 60],
            ['trigger' => 'energy_depleted', 'conditions' => ['relationship' => 'loyal'], 'text' => '*ложится рядом* Ладно, я тоже... отдохну. Но только потому что хочу!', 'priority' => 75],
            ['trigger' => 'order_waiting_long', 'conditions' => ['relationship' => 'loyal'], 'text' => '*терпеливо ждёт* Я жду. Но не потому что доверяю. Просто... жду.', 'priority' => 70],
            ['trigger' => 'high_level_item', 'conditions' => ['order_level' => 'high'], 'text' => '*зрачки как блюдца* БЛЕСТЯЩЕЕ! БОЛЬШОЕ! ХОЧУ!', 'priority' => 85],
            ['trigger' => 'order_appear', 'conditions' => ['streak' => '>=5'], 'text' => '*с уважением кивает* Неплохо работаешь. Для двуногого.', 'priority' => 70],

            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*щурится* Ну давай, удиви меня.', 'priority' => 30, 'max_shows' => 30],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => '*машет хвостом* Хм.', 'priority' => 30, 'max_shows' => 30],
            ['trigger' => 'order_appear', 'conditions' => [], 'text' => '*потягивается* Ладно, ещё один...', 'priority' => 25, 'max_shows' => 30],
            ['trigger' => 'order_complete', 'conditions' => [], 'text' => '*кивает* Принято к сведению.', 'priority' => 25, 'max_shows' => 30],
            ['trigger' => 'player_return', 'conditions' => ['time_of_day' => 'morning'], 'text' => '*зевает* Я не спал. Я наблюдал за территорией.', 'priority' => 60],
            ['trigger' => 'wrong_merge_attempt', 'conditions' => ['relationship' => 'familiar'], 'text' => '*закрывает глаза лапой* Я этого не видел.', 'priority' => 60],
            ['trigger' => 'order_partial', 'conditions' => ['speed' => 'fast'], 'text' => '*ухо дёргается* Быстро. Хм.', 'priority' => 55],
            ['trigger' => 'event_start', 'conditions' => ['relationship' => 'loyal'], 'text' => '*вскакивает* Новое?! Я первый! ПЕРВЫЙ!', 'priority' => 75],
            ['trigger' => 'player_return', 'conditions' => ['relationship' => 'familiar'], 'text' => '*коротко мяукает* Ну. Привет.', 'priority' => 60],
        ];

        $this->insertLines($id, $lines);
    }

    private function insertLines(int $characterId, array $lines): void
    {
        foreach ($lines as $line) {
            CharacterLine::create([
                'character_id' => $characterId,
                'trigger' => $line['trigger'],
                'conditions' => $line['conditions'],
                'text' => $line['text'],
                'priority' => $line['priority'],
                'max_shows' => $line['max_shows'] ?? 10,
                'cooldown_hours' => $line['cooldown_hours'] ?? 24,
            ]);
        }
    }
}
