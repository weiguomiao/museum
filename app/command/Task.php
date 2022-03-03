<?php
declare (strict_types = 1);

namespace app\command;

use app\common\model\Activity;
use app\common\model\ActivityLog;
use app\common\model\BookLog;
use app\common\model\BookPt;
use app\common\model\Card;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Task extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('task')
            ->setDescription('the task command');        
    }

    protected function execute(Input $input, Output $output)
    {
        //预约时间生成
        $bwgPt=BookPt::where('id','>','0')->where('ven_id',1)->order('id','desc')->find();
        $xingqi = date("w", $bwgPt->time+86400);
        if($xingqi==1){//星期一闭馆
            BookPt::create([
                'number'=>0,
                'time'=>$bwgPt->time+86400,
                'ven_id'=>1,
                'status'=>2
            ]);
        }else{
            BookPt::create([
                'number'=>300,
                'time'=>$bwgPt->time+86400,
                'ven_id'=>1,
                'status'=>2
            ]);
        }
        $tsgPt=BookPt::where('id','>','0')->where('ven_id',2)->order('id','desc')->find();
        $xingqi = date("w", $tsgPt->time+86400);
        if($xingqi==1){//星期一闭馆
            BookPt::create([
                'number'=>0,
                'time'=>$tsgPt->time+86400,
                'ven_id'=>2,
                'status'=>2
            ]);
        }else{
            BookPt::create([
                'number'=>300,
                'time'=>$tsgPt->time+86400,
                'ven_id'=>2,
                'status'=>2
            ]);
        }
        //签到过期，变更状态
        $activityLog=ActivityLog::where('status',1)->select();
        foreach ($activityLog as $k=>$v){
            $activity=Activity::where('id',$v['act_id'])->find();
            if($activity->getData('sign_end_time')<time()){
                $v->status=3;
                $v->save();
            }
        }
        //预约过期，变更状态
        $bookLog=BookLog::where('status',1)->select();
        foreach ($bookLog as $k=>$v){
            $book=BookPt::where('id',$v['book_id'])->find();
            if(($book->time+86399)<time()){
                $v->status=3;
                $v->save();
            }
        }
        //判断卡是否过期
        $cardList=Card::where('status',1)->where('is_act',1)->select();
        foreach ($cardList as $k=>$v){
            if($v['expire_time']<time()){
                $v->is_act=3;
                $v->save();
            }
        }
        $output->writeln('execute_time：'.date('Y-m-d H:i:s',time()));
    }
}
