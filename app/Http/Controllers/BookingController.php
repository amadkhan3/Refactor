<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;


/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    /**
     * @var BookingRepository
     */
    protected $repository;


    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository) {$this->repository = $bookingRepository;}


    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($user_id = $request->get('user_id')) {
            return response($this->repository->getUsersJobs($user_id));
        } else if (
            $request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')
        ) {
            return response($this->repository->getAll($request));
        }
    }


    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return response($this->repository->with('translatorJobRel.user')->find($id));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        return response($this->repository->store($request->__authenticatedUser, $request->all()));
    }


    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        return response($this->repository->updateJob($id, array_except($request->all(), ['_token', 'submit']), $request->__authenticatedUser));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        return response($this->repository->storeJobEmail($request->all()));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if ($request->get('user_id')) {
            return response($this->repository->getUsersJobsHistory($request->get('user_id'), $request));
        }

        return null;
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        return response($this->repository->acceptJob($request->all(), $request->__authenticatedUser));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJobWithId(Request $request)
    {
        return response($this->repository->acceptJobWithId($request->get('job_id'), $request->__authenticatedUser));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        return response($this->repository->cancelJobAjax($request->all(), $request->__authenticatedUser));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        return response($this->repository->endJob($request->all()));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function customerNotCall(Request $request)
    {
        return response($this->repository->customerNotCall($request->all()));
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        return response($this->repository->getPotentialJobs($request->__authenticatedUser));
    }


    /**
     * @param Request $request
     * @return string
     */
    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        
        if($data['flagged'] == 'true' && $data['admincomment'] == '') return "Please, add comment";
        
        $distance = (isset($data['distance']) && $data['distance'] != "") ? $data['distance'] : null;
        $time = (isset($data['time']) && $data['time'] != "") ? $data['time'] : null;
        $jobid = (isset($data['jobid']) && $data['jobid'] != "") ? $data['jobid'] : null;
        $session = (isset($data['session_time']) && $data['session_time'] != "") ? $data['session_time'] : null;
        $admincomment = (isset($data['admincomment']) && $data['admincomment'] != "") ? $data['admincomment'] : null;
        $flagged = $data['flagged'] == 'true' ? 'yes' : 'no';
        $manually_handled = $data['manually_handled'] == 'true' ? 'yes' : 'no';
        $by_admin = $data['by_admin'] == 'true' ? 'yes' : 'no';


        if ($time && $distance && $jobid) Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));

        if ($admincomment && $session && $flagged == 'yes' && $manually_handled == 'yes' && $by_admin == 'yes') Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        return response('Record updated!');
    }


    public function reopen(Request $request)
    {
        return response($this->repository->reopen($request->all()));
    }


    /**
     * @param Request $request
     * @return array
     */
    public function resendNotifications(Request $request)
    {
        $job = $this->repository->find($request->get('jobid'));
        $this->repository->sendNotificationTranslator($job, $this->repository->jobToData($job), '*');
        
        return response(['success' => 'Push sent']);
    }


    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        try {
            $this->repository->sendSMSNotificationToTranslator($this->repository->find($request->get('jobid')));
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
