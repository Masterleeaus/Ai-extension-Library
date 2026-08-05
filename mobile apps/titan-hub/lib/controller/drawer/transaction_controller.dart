import '../../backend/model/common/common_success_model.dart';
import '../../backend/model/transaction_log/transaction_log_model.dart';
import '../../backend/services/api_services.dart';
import '../../backend/utils/logger.dart';
import '../../routes/routes.dart';
import '../../utils/basic_screen_imports.dart';
import '../../widgets/others/congratulation_widget.dart';

final log = logger(TransactionController);

class TransactionController extends GetxController {
  RxList<TextEditingController> inputFieldControllers =
      <TextEditingController>[].obs;
  RxList inputFields = [].obs;
  List<String> listFieldName = [];
  RxList inputFileFields = [].obs;
  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late TransactionLogModel _transactioData;
  TransactionLogModel get transactioData => _transactioData;

  @override
  void onInit() {
    super.onInit();
    getTransactionData();
  }

  // --------------------------- Api function ----------------------------------
  Future<TransactionLogModel> getTransactionData() async {
    inputFields.clear();
    inputFieldControllers.clear();
    _isLoading.value = true;
    update();

    await ApiServices.getTransactionLogAPi().then((value) {
      _transactioData = value!;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _transactioData;
  }

  final _isTatumConfirmLoading = false.obs;
  bool get isTatumConfirmLoading => _isTatumConfirmLoading.value;
  late CommonSuccessModel _addMoneyConfirm;
  CommonSuccessModel get addMoneyConfirm => _addMoneyConfirm;

  // Profile TatumConfirm process with image
  Future<CommonSuccessModel> tatumConfirmProcess(context, String url) async {
    _isTatumConfirmLoading.value = true;
    update();

    Map<String, String> inputBody = {};
    final data = _transactioData.data.transactions.addMoney[1].dynamicInputs;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    await ApiServices.tatumConfirmApiProcess(
      body: inputBody,
      url: url,
    ).then((value) {
      _addMoneyConfirm = value!;
      StatusScreen.show(
        context: context,
        subTitle: Strings.yourMoneyAddedSucces.tr,
        onPressed: () {
          Get.offAllNamed(Routes.bottomNavBarScreen);
        },
      );
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isTatumConfirmLoading.value = false;
    update();
    return _addMoneyConfirm;
  }
}
