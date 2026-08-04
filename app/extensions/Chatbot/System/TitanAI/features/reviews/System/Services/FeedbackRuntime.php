<?php
namespace App\Extensions\Reviews\System\Services;use App\Extensions\Reviews\System\Models\Feedback;
class FeedbackRuntime{public function record(array$data):Feedback{$data['kind']??='feedback';return Feedback::query()->create($data);}public function nps(int$score):string{return $score>=9?'promoter':($score>=7?'passive':'detractor');}public function complaint(array$data):Feedback{$data['kind']='complaint';$data['status']='open';return $this->record($data);}public function testimonial(Feedback$f):Feedback{$f->forceFill(['is_testimonial'=>true])->save();return$f->refresh();}}
