<?php
// Safe extension upgrade entry point for customer-tags. The host installer remains authoritative.
return ['extension'=>'customer-tags','strategy'=>'additive-migrations','preserve_existing_data'=>true,'clear_cache'=>true];
