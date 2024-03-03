<?php

namespace Shlinkio\Shlink\CLI\RedirectRule;

enum RedirectRuleHandlerAction: string
{
    case ADD = 'Add new rule';
    case REMOVE = 'Remove existing rule';
    case RE_ARRANGE = 'Re-arrange rule';
    case SAVE = 'Save and exit';
    case DISCARD = 'Discard changes';
}
