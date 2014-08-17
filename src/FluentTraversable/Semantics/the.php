<?php

namespace FluentTraversable\Semantics;

use FluentTraversable\Puppet;

/**
 * @inheritdoc
 *
 * This class is alias to {@link FluentTraversable\Puppet} class, its exists only for semantic reason. Unfortunately
 * class_alias() function is not supported by popular IDE and code completion does not work so this walkaround is
 * necessary.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
final class the extends Puppet
{
}