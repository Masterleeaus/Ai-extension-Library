<?php
// Safe extension upgrade entry point for reviews. The host installer remains authoritative.
return ['extension'=>'reviews','strategy'=>'additive-migrations','preserve_existing_data'=>true,'clear_cache'=>true];
